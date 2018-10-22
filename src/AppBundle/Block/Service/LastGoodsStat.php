<?php
namespace AppBundle\Block\Service;

use Doctrine\ORM\EntityManager;
use Sonata\BlockBundle\Block\BlockContextInterface;
use Sonata\BlockBundle\Block\Service\AbstractBlockService;
use Sonata\BlockBundle\Block\Service\BlockServiceInterface;
use Sonata\BlockBundle\Model\BlockInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class LastGoodsStat extends AbstractBlockService implements BlockServiceInterface
{

    /**
     * @var EntityManager
     */
    protected static $em;

    public function __construct(EntityManager $entityManager, $engineInterface )
    {
        parent::__construct(null, $engineInterface);
        self::$em = $entityManager;
        self::$em->getConnection()->getConfiguration()->setSQLLogger(null);
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function configureOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults([
            'title' => 'LastGoodsStat',
            'template' => 'AppBundle:Block:last.goods.stat.block.html.twig',
        ]);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return "LastGoodsStat";
    }

    /**
     * @param BlockInterface $block
     * @return array
     */
    public function getCacheKeys(BlockInterface $block)
    {
        return [];
    }

    /**
     * @param $media
     * @return array
     */
    public function getJavascripts($media)
    {
        return [];
    }

    public function getStylesheets($media)
    {
        return [];
    }

    /**
     * @param BlockContextInterface $blockContext
     * @param Response|null $response
     * @return Response
     */
    public function execute(BlockContextInterface $blockContext, Response $response = null)
    {
        $repo = self::$em->getRepository('AppBundle:GoodsStat');
        $query = $repo
            ->createQueryBuilder('gs')
            ->select('gs.goodsId, COUNT(gs.id) AS cnt')
            ->groupBy('gs.goodsId')
            ->orderBy('cnt', 'DESC')
            ->addOrderBy('gs.goodsId')
            ->setMaxResults(5)
            ->getQuery();
        $goodsStats = $query->getResult();
        $goods = [];
        foreach ($goodsStats as $goodsStat) {
            $goods[] = self::$em->getRepository('AppBundle:Goods')->findOneBy(['id' => $goodsStat['goodsId']]);
        }

        return $this->renderResponse('AppBundle:Block:last.goods.stat.block.html.twig', [
            'block' => $blockContext->getBlock(),
            'settings' => $blockContext->getSettings(),
            'info' => [
                'title' => 'Популярные товары',
                'goods' => $goods,
            ],
        ], $response);
    }

    /**
     * @param $category
     * @return array|bool
     */
    public function getTopCategoryVendorsCounts($category)
    {
        $lessGoodsVendors = [];
        if ($category) {
            $categoryData = $category->getData();
            if (isset($categoryData['emptyVendors'])) {
                if (isset($categoryData['topVendors'])) {
                    $topVendors = $categoryData['topVendors'];
                }
                $emptyVendors = $categoryData['emptyVendors'];
                foreach ($emptyVendors as $emptyVendorAlias => $emptyVendorCount) {
                    if (isset($topVendors) && isset($topVendors[$emptyVendorAlias])) {
                        if (($emptyVendorCount / 3 >= $topVendors[$emptyVendorAlias]) || ($topVendors[$emptyVendorAlias] / 3 >= $emptyVendorCount)) {
                            $lessGoodsVendors[] = [
                                'category' => $category,
                                'topVendorName' => $emptyVendorAlias,
                                'topVendorCount' => $topVendors[$emptyVendorAlias],
                                'realCount' => $emptyVendorCount,
                            ];
                        }
                    }
                }
            }
        }

        return [
            'category' => $category,
            'lessGoodsVendorsCount' => count($lessGoodsVendors),
        ];
    }
}

