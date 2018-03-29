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
        $lessGoodsArray = [];
        $repo = self::$em->getRepository('AppBundle:Category');
        $query = $repo
            ->createQueryBuilder('c')
            ->where('c.isActive = 1')
            ->andWhere('c.parentCategory IS NOT NULL')
            ->getQuery();
        $categories = $query->getResult();

        foreach ($categories as $category) {
            if (count($lessGoodsArray) < 10) {
                $lessGoodsArray += $this->getTopCategoryVendorsCounts($category);
            } else {
                break;
            }
        }

        return $this->renderResponse('AppBundle:Block:l4w.info.block.html.twig', [
            'block' => $blockContext->getBlock(),
            'settings' => $blockContext->getSettings(),
            'info' => [
                'title' => 'Несоответствия в категория+бренд',
                'lessGoodsArray' => $lessGoodsArray,
            ],
        ], $response);
    }

    /**
     * @param $category
     * @return array
     */
    public function getTopCategoryVendorsCounts($category)
    {
        if ($category) {
            $lessGoodsVendors = [];
            $excludeWords = explode(';', $category->getExcludeWords());
            $searchString = $category->getSearchString();
            if (array_filter($excludeWords)) {
                $searchString .= ' -' . implode(' -', $excludeWords);
            }
            $categoryData = $category->getData();
            if (isset($categoryData['topVendors'])) {
                $topVendors = $categoryData['topVendors'];
                foreach ($topVendors as $topVendorName => $topVendorCount) {
                    if (count($lessGoodsVendors) < 10) {
                        $searchStringWithVendor = $searchString . ' and @vendoralias =' . $topVendorName;
                        $searchGoods = $this->searchByStringAndLimit($searchStringWithVendor, 1);
                        $totalCount = $searchGoods['total_found'];
                        if ($totalCount < 4 && $totalCount != $topVendorCount) {
                            $lessGoodsVendors[] = [
                                'category' => $category,
                                'topVendorName' => $topVendorName,
                                'topVendorCount' => $topVendorCount,
                                'realCount' => $totalCount,
                            ];
                        }
                    } else {
                        break;
                    }
                }
            }
            return $lessGoodsVendors;
        }

    }

    /**
     * @param $searchString
     * @param $limit
     * @return mixed
     */
    public function searchByStringAndLimit($searchString, $limit)
    {
        self::$sphinxSearch->setLimits(0, $limit, 100000);
        self::$sphinxSearch->SetMatchMode(SPH_MATCH_EXTENDED);
        return self::$sphinxSearch->query($searchString);
    }
}

