<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Category;
use AppBundle\Entity\SeoText;
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
    protected static $seoDescription = '';

    /**
     * @var string
     */
    protected static $pageTitle = '';

    /**
     * @Route("/", name="homepage",
     *      options={"sitemap" = true})
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
        $categoryTopVendors = [];
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
            $excludeWords = array_filter($excludeWords);
            $searchString = $actualCategory->getSearchString();
            $searchString .= ' -' . implode(' -', $excludeWords);
            $searchGoods = $this->searchByString($searchString, $request->query->getInt('page', 0));
            if (isset($searchGoods['matches'])) {
                $matches = $searchGoods['matches'];
            }
            $totalCount = $searchGoods['total_found'];
            if (count($actualCategory->getChildrenCategories()) > 0) {
                foreach ($actualCategory->getChildrenCategories() as $childrenCategory) {
                    if ($childrenCategory->getIsActive()) {
                        $excludeWords = explode(';', $childrenCategory->getExcludeWords());
                        $excludeWords = array_filter($excludeWords);
                        $searchString = $childrenCategory->getSearchString();
                        $searchString .= ' -' . implode(' -', $excludeWords);
                        $searchGoods = $this->searchByStringAndLimit($searchString, 10);
                        if (isset($searchGoods['matches'])) {
                            $categoryImage = json_decode(end($searchGoods['matches'])['attrs']['pictures'])[0];
                        }
                        if ($searchGoods['total_found'] > 0) {
                            $childrenCategories[] = [
                                'category' => $childrenCategory,
                                'image' => $categoryImage,
                                'url' => self::getParentCategoriesUrl($childrenCategory) . $childrenCategory->getAlias(),
                            ];
                        }
                    }
                }
            }
            $parentsUrl = $this->getParentCategoriesUrl($actualCategory);
            $actualUrl = $parentsUrl . $actualCategory->getAlias();
            $pagination = [
                'url' => $actualUrl . '?',
                'currentPage' => $request->query->getInt('page', 1),
                'totalPagesCount' => ceil($totalCount / self::$resultsOnPage),
            ];
        }
        if (isset($actualCategory->getData()['topVendors'])) {
            $categoryTopVendors = $actualCategory->getData()['topVendors'];
        }
        $categoryTopVendorsResult = [];
        if ($categoryTopVendors) {
            foreach ($categoryTopVendors as $categoryTopVendorAlias => $categoryTopVendorCount) {
                $categoryTopVendor = self::$em
                    ->getRepository('AppBundle:Vendor')
                    ->findOneBy([
                        'alias' => $categoryTopVendorAlias
                    ]);
                if ($categoryTopVendor) {
                    $imgUrl = '';
                    if (file_exists($this->get('kernel')->getRootDir() . '/../web/media/brands/' . $categoryTopVendorAlias . '.png')) {
                        $imgUrl = $categoryTopVendorAlias . '.png';
                    }
                    $categoryTopVendorsResult[] = [
                        'alias' => $categoryTopVendorAlias,
                        'name' => $categoryTopVendor->getName(),
                        'count' => $categoryTopVendorCount,
                        'imgUrl' => $imgUrl,
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
            'seoDescription' => $actualCategory->getDescription() ? $actualCategory->getDescription() : $actualCategory->getSeoTitle(),
            'childrenCategories' => $childrenCategories,
            'pagination' => $pagination,
            'parentsUrl' => $parentsUrl,
            'actualUrl' => $actualUrl,
        ]);
    }

    /**
     * @Route("/catalog_page", name="catalogPage",
     *      options={"sitemap" = true})
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function catalogPageAction()
    {
        $catalogCategories = [];
        self::$em = $this->getDoctrine()->getManager();
        self::$em->getConnection()->getConfiguration()->setSQLLogger(null);
        $categories = self::$em
            ->getRepository('AppBundle:Category')
            ->findBy([
                'isActive' => true,
                'parentCategory' => null,
            ]);
        foreach ($categories as $category) {
            $excludeWords = explode(';', $category->getExcludeWords());
            $excludeWords = array_filter($excludeWords);
            $searchString = $category->getSearchString();
            $searchString .= ' -' . implode(' -', $excludeWords);
            $searchGoods = $this->searchByStringAndLimit($searchString, 10);
            if (isset($searchGoods['matches'])) {
                $categoryImage = json_decode(end($searchGoods['matches'])['attrs']['pictures'])[0];
            }
            $catalogCategories[] = [
                'category' => $category,
                'image' => $categoryImage,
                'url' => '/catalog/' . $category->getAlias(),
            ];
        }

        return $this->render('AppBundle:look4wear:catalog.html.twig', [
            'seoTitle' => self::$seoTitle,
            'seoDescription' => 'Каталог look4wear.ru - отличная и удобная платформа для выбора одежды по Вашему вкусу!',
            'pageTitle' => self::$pageTitle,
            'catalogCategories' => $catalogCategories,
        ]);
    }

    /**
     * @Route("/site_map", name="sitemap_page",
     *      options={"sitemap" = true})
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function sitemapPageAction()
    {
        self::$em = $this->getDoctrine()->getManager();
        self::$em->getConnection()->getConfiguration()->setSQLLogger(null);
        $categories = self::$em
            ->getRepository('AppBundle:Category')
            ->findBy([
                'isActive' => true,
                'parentCategory' => null,
            ]);

        return $this->render('AppBundle:look4wear:sitemap.html.twig', [
            'seoTitle' => 'Карта сайта look4wear.ru',
            'pageTitle' => self::$pageTitle,
            'seoDescription' => 'Карта сайта look4wear.ru',
            'parentCategories' => $categories,
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
            $excludeWords = array_filter($excludeWords);
            $searchString = $category->getSearchString();
            if ($vendor) {
//                $searchString .= ' and @vendorAlias =' . $vendorAlias;
                $searchString .= ' ' . $vendorAlias;
                $seoText = self::$em
                    ->getRepository('AppBundle:SeoText')
                    ->findOneBy([
                        'alias' => $categoryAlias . '/' . $vendorAlias
                    ]);
                if ($seoText) {
                    self::$seoDescription = $seoText->getText();
                }
            }
            $searchString .= ' -' . implode(' -', $excludeWords);
            $searchGoods = $this->searchByString($searchString, $request->query->getInt('page', 0));
            if (isset($searchGoods['matches'])) {
                $matches = $searchGoods['matches'];
            }
            $totalCount = $searchGoods['total_found'];
            $pagination = [
                'url' => '/filter/' . $categoryAlias . '/' . $vendorAlias . '?',
                'currentPage' => $request->query->getInt('page', 1),
                'totalPagesCount' => ceil($totalCount / self::$resultsOnPage),
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
                        $imgUrl = '';
                        if (file_exists($this->get('kernel')->getRootDir() . '/../web/media/brands/' . $categoryTopVendorAlias . '.png')) {
                            $imgUrl = $categoryTopVendorAlias . '.png';
                        }
                        $categoryTopVendorsResult[] = [
                            'alias' => $categoryTopVendorAlias,
                            'name' => $categoryTopVendor->getName(),
                            'count' => $categoryTopVendorCount,
                            'imgUrl' => $imgUrl,
                        ];
                    }
                }
            }
        }
        if ($category && $vendor) {
            self::$seoTitle = $category->getTitle() . ' ' . $vendor->getName() .
                '. Купить в интернет-магазине по выгодной цене и с доставкой по России.';
            self::$pageTitle = ucfirst($category->getTitle()) . ' ' . $vendor->getName();
            if (!self::$seoDescription) {
                self::$seoDescription = self::$seoTitle;
            }
            $parentsUrl = $this->getParentCategoriesUrl($category);
        }

        $otherCategories = [];
        $qb = self::$em->createQueryBuilder();
        $qb->select('c')
            ->from('AppBundle:Category', 'c')
            ->where('c.parentCategory != 0')
            ->andWhere('REGEXP(c.data, :regexp) = true')
            ->andWhere('c.isActive = 1')
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
            'pagination' => $pagination,
            'category' => $category,
            'categoryTopVendorsResult' => $categoryTopVendorsResult,
            'otherCategories' => $otherCategories,
            'vendorAlias' => $vendorAlias,
            'parentsUrl' => $parentsUrl,
            'seoDescription' => self::$seoDescription,
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
            'totalPagesCount' => ceil($totalCount / self::$resultsOnPage),
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
     * @Route("/about", name="about_page",
     *      options={"sitemap" = true})
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function aboutAction(Request $request)
    {

        return $this->render('AppBundle:look4wear:about.html.twig', [
            'seoTitle' => 'О проекте look4wear.ru',
            'pageTitle' => '',
            'seoDescription' => 'look4wear.ru – ваш незаменимый помощник в шоппинге! На нашем портале собрана мужская и женская одежда самых известных марок,
             а также обувь и аксессуары. Модные бренды размещают здесь свои каталоги, чтобы вы могли сделать покупки всего за пару кликов.',
            'childrenCategories' => [],
        ]);
    }

    /**
     * @Route("/shipping", name="shipping_page",
     *      options={"sitemap" = true})
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function shippingAction(Request $request)
    {

        return $this->render('AppBundle:look4wear:shipping.html.twig', [
            'seoTitle' => 'Доставка и возврат',
            'pageTitle' => '',
            'seoDescription' => 'Условия доставки товаров в каталоге look4wear.ru. Возможность возврата товара в магазин.',
            'childrenCategories' => [],
        ]);
    }

    /**
     * @Route("/goods/buy/{alias}", name="goods_buy_route")
     */
    public function productBuyAction($alias)
    {
        $em = $this->getDoctrine()->getManager();
        $goods = $em
            ->getRepository('AppBundle:Goods')
            ->findOneBy(array('alias' => $alias));
        if (!$goods) {
            throw $this->createNotFoundException('The $goods does not exist');
        }
        return $this->redirect($goods->getURl());
    }

    /**
     * @param $searchString
     * @param $page
     * @return mixed
     */
    private function searchByString($searchString, $page)
    {
        if ($page > 0) {
            $page -= 1;
        }
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
