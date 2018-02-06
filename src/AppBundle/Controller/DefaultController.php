<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Category;
use AppBundle\Entity\Vendor;
use Doctrine\ORM\EntityManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class DefaultController extends Controller
{
    /**
     * @var EntityManager
     */
    protected static $em;

    /**
     * @var int
     */
    protected static $resultsOnPage = 20;

    /**
     * @var string
     */
    protected static $seoTitle = '';

    /**
     * @var string
     */
    protected static $pageTitle = '';

    /**
     * @Route("/", name="homepage")
     */
    public function indexAction(Request $request)
    {
        return $this->render('AppBundle:look4wear:index.html.twig', array(
            'base_dir' => realpath($this->container->getParameter('kernel.root_dir') . '/..') . DIRECTORY_SEPARATOR,
        ));
    }

//    /**
//     * @Route("/vendors", name="vendors")
//     */
//    public function vendorsAction(Request $request)
//    {
//        self::$em = $this->getDoctrine()->getManager();
//        $vendorsCounts = [];
//        $vendors = self::$em
//            ->getRepository('AppBundle:Vendor')
//            ->findBy([]);
//        /** @var Vendor $vendor */
//        foreach ($vendors as $vendor) {
//            $sphinxSearch = $this->get('iakumai.sphinxsearch.search');
//            $sphinxSearch->setLimits(0, self::$resultsOnPage);
//            $sphinxSearch->SetMatchMode(SPH_MATCH_EXTENDED);
//            $searchGoods = $sphinxSearch->query($vendor->getName());
//            $totalCount = $searchGoods['total_found'];
//            if ($totalCount > 2000) {
//                $vendorsCounts[$vendor->getId()] = $totalCount;
//            }
//        }
//        return $this->render('AppBundle:look4wear:vendors.html.twig', []);
//    }

    /**
     * @Route("/vendor/{alias}", name="vendor")
     */
    public function vendorAction($alias, Request $request)
    {
        self::$em = $this->getDoctrine()->getManager();
        $goods = [];
        $totalCount = 0;
        $vendor = self::$em
            ->getRepository('AppBundle:Vendor')
            ->findOneBy([
                'alias' => $alias
            ]);
        if ($vendor) {
            $qb = self::$em->createQueryBuilder();
            $qb->select('Goods')
                ->from('AppBundle:Goods', 'Goods')
                ->where('Goods.Vendor = :vendorId')
                ->andWhere('Goods.isDelete = 0')
                ->setParameter('vendorId', $vendor->getId())
                ->setMaxResults(self::$resultsOnPage);
            $query = $qb->getQuery();
            $goods = $query->getResult();
            $totalCount = count($goods);
        }

        return $this->render('AppBundle:look4wear:vendor.html.twig', [
            'goods' => $goods,
            'totalCount' => $totalCount,
        ]);
    }

    /**
     * @Route("/catalog/{token}", name="catalog", requirements={"token"=".+"})
     * @param $token
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function catalogAction($token, Request $request)
    {
        self::$em = $this->getDoctrine()->getManager();
        self::$em->getConnection()->getConfiguration()->setSQLLogger(null);
        $actualCategory = null;
        $categoryAliases = explode('/', $token);
        $categories = [];
        foreach ($categoryAliases as $categoryAlias) {
            $categories[] = self::$em
                ->getRepository('AppBundle:Category')
                ->findOneBy([
                    'alias' => $categoryAlias
                ]);
        }
        $categories = array_filter($categories);
        $categoryAliases = array_filter($categoryAliases);
        if (count($categoryAliases) == count($categories)) {
            $actualCategory = end($categories);
        }
        $matches = [];
        $totalCount = 0;
        $childrenCategories = [];
        $pagination = null;
        if ($actualCategory) {
            $excludeWords = explode(';', $actualCategory->getExcludeWords());
            $searchString = $actualCategory->getSearchString();
            if (array_filter($excludeWords)) {
                $searchString .= ' -' . implode(' -', $excludeWords);
            }
            $searchGoods = $this->searchByString($searchString, $request->query->getInt('page', 0));
            if (isset($searchGoods['matches'])) {
                $matches = $searchGoods['matches'];
            }
            $totalCount = $searchGoods['total_found'];
            if (count($actualCategory->getChildrenCategories()) > 0) {
                $childrenCategories = $actualCategory->getChildrenCategories();
            }
            $parentsUrl = $this->getParentCategoriesUrl($actualCategory);
            $actualUrl = $parentsUrl . $actualCategory->getAlias();
            $pagination = [
                'url' => $actualUrl . '?',
                'currentPage' => $request->query->getInt('page', 1),
                'totalPagesCount' => floor($totalCount / self::$resultsOnPage),
            ];
        }
        $categoryTopVendors = $actualCategory->getData()['topVendors'];
        $categoryTopVendorsResult = [];
        if ($categoryTopVendors) {
            foreach ($categoryTopVendors as $categoryTopVendorAlias => $categoryTopVendorCount) {
                $categoryTopVendor = self::$em
                    ->getRepository('AppBundle:Vendor')
                    ->findOneBy([
                        'alias' => $categoryTopVendorAlias
                    ]);
                if ($categoryTopVendor) {
                    $categoryTopVendorsResult[] = [
                        'alias' => $categoryTopVendorAlias,
                        'name' => $categoryTopVendor->getName(),
                        'count' => $categoryTopVendorCount,
                    ];
                }
            }
        }

        return $this->render('AppBundle:look4wear:category.html.twig', [
            'breadcrumbs' => $this->getBreadcrumbs($actualCategory),
            'category' => $actualCategory,
            'categoryTopVendorsResult' => $categoryTopVendorsResult,
            'goods' => $matches,
            'totalCount' => $totalCount,
            'pageTitle' => $actualCategory->getTitle(),
            'seoTitle' => $actualCategory->getSeoTitle(),
            'childrenCategories' => $childrenCategories,
            'pagination' => $pagination,
            'parentsUrl' => $parentsUrl,
            'actualUrl' => $actualUrl,
        ]);
    }


    /**
     * @Route("/filter/{categoryAlias}/{vendorAlias}", name="filter")
     * @param $categoryAlias
     * @param $vendorAlias
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function filterAction($categoryAlias, $vendorAlias, Request $request)
    {
        $matches = [];
        $childrenCategories = [];
        $totalCount = 0;
        self::$em = $this->getDoctrine()->getManager();
        self::$em->getConnection()->getConfiguration()->setSQLLogger(null);
        /** @var Category $category */
        $category = self::$em
            ->getRepository('AppBundle:Category')
            ->findOneBy([
                'alias' => $categoryAlias
            ]);
        /** @var Category $category */
        $vendor = self::$em
            ->getRepository('AppBundle:Vendor')
            ->findOneBy([
                'alias' => $vendorAlias
            ]);
        if ($category) {
            $excludeWords = explode(';', $category->getExcludeWords());
            $searchString = $category->getSearchString();
            if ($vendor) {
                $searchString .= ' ' . $vendorAlias;
            }
            if (array_filter($excludeWords)) {
                $searchString .= ' -' . implode(' -', $excludeWords);
            }
            $searchGoods = $this->searchByString($searchString, $request->query->getInt('page', 0));
            if (isset($searchGoods['matches'])) {
                $matches = $searchGoods['matches'];
            }
            $totalCount = $searchGoods['total_found'];
            $childrenCategories = $category->getChildrenCategories();
            $pagination = [
                'url' => '/filter/' . $categoryAlias . '/' . $vendorAlias . '?',
                'currentPage' => $request->query->getInt('page', 1),
                'totalPagesCount' => floor($totalCount / self::$resultsOnPage),
            ];
            $categoryTopVendors = $category->getData()['topVendors'];
            $categoryTopVendorsResult = [];
            if ($categoryTopVendors) {
                foreach ($categoryTopVendors as $categoryTopVendorAlias => $categoryTopVendorCount) {
                    $categoryTopVendor = self::$em
                        ->getRepository('AppBundle:Vendor')
                        ->findOneBy([
                            'alias' => $categoryTopVendorAlias
                        ]);
                    if ($categoryTopVendor && $categoryTopVendorAlias != $vendorAlias) {
                        $categoryTopVendorsResult[] = [
                            'alias' => $categoryTopVendorAlias,
                            'name' => $categoryTopVendor->getName(),
                            'count' => $categoryTopVendorCount,
                        ];
                    }
                }
            }
        }
        if ($category && $vendor) {
            self::$seoTitle = 'Купить со скидкой ' . mb_strtolower($category->getTitle(), 'utf-8') . ' ' . $vendor->getName();
            self::$pageTitle = ucfirst($category->getTitle()) . ' ' . $vendor->getName();
            $parentsUrl = $this->getParentCategoriesUrl($category);
        }

        $otherCategories = [];
        $qb = self::$em->createQueryBuilder();
        $qb->select('c')
            ->from('AppBundle:Category', 'c')
            ->where('c.parentCategory != 0')
            ->andWhere('REGEXP(c.data, :regexp) = true')
            ->setParameter('regexp', '[[:<:]]' . $vendorAlias . '[[:>:]]');
        $categories = $qb->getQuery()->getResult();
        /** @var Category $categoryItem */
        foreach ($categories as $categoryItem) {
            if ($categoryItem->getId() != $category->getId()) {
                $otherCategories[$categoryItem->getAlias()] = [
                    'title' => $categoryItem->getTitle(),
                    'count' => $categoryItem->getData()['topVendors'][$vendorAlias],
                ];
                if ($vendor) {
                    $otherCategories[$categoryItem->getAlias()]['vendorName'] = $vendor->getName();
                }
            }
        }
        arsort($otherCategories);
        $otherCategories = array_slice($otherCategories, 0, 20);

        return $this->render('AppBundle:look4wear:filter.html.twig', [
            'goods' => $matches,
            'totalCount' => $totalCount,
            'seoTitle' => self::$seoTitle,
            'pageTitle' => self::$pageTitle,
            'childrenCategories' => $childrenCategories,
            'pagination' => $pagination,
            'category' => $category,
            'categoryTopVendorsResult' => $categoryTopVendorsResult,
            'otherCategories' => $otherCategories,
            'vendorAlias' => $vendorAlias,
            'parentsUrl' => $parentsUrl,
        ]);
    }

    /**
     * @Route("/search", name="search_page")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function searchAction(Request $request)
    {
        $matches = [];
        $searchString = $request->get('searchString');
        $searchGoods = $this->searchByString($searchString, $request->query->getInt('page', 0));
        if (isset($searchGoods['matches'])) {
            $matches = $searchGoods['matches'];
        }
        $totalCount = $searchGoods['total_found'];
        $pagination = [
            'url' => '/search' . '?searchString=' . $searchString . '&',
            'currentPage' => $request->query->getInt('page', 1),
            'totalPagesCount' => floor($totalCount / self::$resultsOnPage),
        ];

        return $this->render('AppBundle:look4wear:search.html.twig', [
            'goods' => $matches,
            'seoTitle' => '',
            'pageTitle' => '',
            'childrenCategories' => [],
            'totalCount' => $totalCount,
            'searchString' => $searchString,
            'pagination' => $pagination,
        ]);
    }

    /**
     * @param $searchString
     * @param $page
     * @return mixed
     */
    private function searchByString($searchString, $page)
    {
        $sphinxSearch = $this->get('iakumai.sphinxsearch.search');
        $sphinxSearch->setLimits($page * self::$resultsOnPage, self::$resultsOnPage, 100000);
        $sphinxSearch->SetMatchMode(SPH_MATCH_EXTENDED);
        return $sphinxSearch->query($searchString);
    }

    /**
     * @param $searchString
     * @param $limit
     * @return mixed
     */
    public function searchByStringAndLimit($searchString, $limit)
    {
        $sphinxSearch = $this->get('iakumai.sphinxsearch.search');
        $sphinxSearch->setLimits(0, $limit, 100000);
        $sphinxSearch->SetMatchMode(SPH_MATCH_EXTENDED);
        return $sphinxSearch->query($searchString);
    }

    /**
     * @param Category $category
     * @return array
     */
    private function getBreadcrumbs(Category $category)
    {
        $breadcrumbs[] = ['link' => $this->getParentCategoriesUrl($category) . $category->getAlias(), 'title' => $category->getName()];
        $parentCategory = $category->getParentCategory();
        while ($parentCategory) {
            $breadcrumbs[] = ['link' => $this->getParentCategoriesUrl($parentCategory) . $parentCategory->getAlias(), 'title' => $parentCategory->getName()];
            $parentCategory = $parentCategory->getParentCategory();
        }
        $breadcrumbs[] = ['link' => '/', 'title' => 'Главная'];

        return array_reverse($breadcrumbs);
    }

    /**
     * @param Category $category
     * @return string
     */
    private function getParentCategoriesUrl(Category $category)
    {
        $parentCategories = [];
        $parentsUrl = '/catalog/';
        $parentCategories[] = $parentCategory = $category->getParentCategory();
        while ($parentCategory && $parentCategories[] = $parentCategory = $parentCategory->getParentCategory()) {
        }
        if (!end($parentCategories)) {
            array_pop($parentCategories);
        }
        $parentCategories = array_reverse($parentCategories);
        foreach ($parentCategories as $parentCategory) {
            $parentsUrl .= $parentCategory->getAlias() . '/';
        }

        return $parentsUrl;
    }
}
