<?php
namespace AppBundle\Block\Service;

use Doctrine\ORM\EntityManager;
use Sonata\BlockBundle\Block\BlockContextInterface;
use Sonata\BlockBundle\Block\Service\AbstractBlockService;
use Sonata\BlockBundle\Block\Service\BlockServiceInterface;
use Sonata\BlockBundle\Model\BlockInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class L4wInfo extends AbstractBlockService implements BlockServiceInterface
{

    /**
     * @var EntityManager
     */
    protected static $em;

    protected static $sphinxSearch;

    public function __construct(EntityManager $entityManager, $sphinxSearch, $engineInterface )
    {
        parent::__construct(null, $engineInterface);
        self::$em = $entityManager;
        self::$sphinxSearch = $sphinxSearch;
        self::$em->getConnection()->getConfiguration()->setSQLLogger(null);
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function configureOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults([
            'title' => 'L4wInfo',
            'template' => 'AppBundle:Block:l4w.info.block.html.twig',
        ]);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return "L4wInfo";
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
        $badCategories = [];
        $repo = self::$em->getRepository('AppBundle:Category');
        $query = $repo
            ->createQueryBuilder('c')
            ->where('c.isActive = 1')
            ->andWhere('c.parentCategory IS NOT NULL')
            ->getQuery();
        $categories = $query->getResult();

        foreach ($categories as $category) {
            $lessGoodsArray = $this->getTopCategoryVendorsCounts($category);
            if ($lessGoodsArray) {
                $badCategories[$category->getTitle()] = $lessGoodsArray[0];
            }
        }

        return $this->renderResponse('AppBundle:Block:l4w.info.block.html.twig', [
            'block' => $blockContext->getBlock(),
            'settings' => $blockContext->getSettings(),
            'info' => [
                'title' => 'Категории с существенным различием товаров',
                'badCategories' => $badCategories,
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

