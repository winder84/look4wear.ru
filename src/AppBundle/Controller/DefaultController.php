<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Category;
use AppBundle\Entity\Vendor;
use Doctrine\Common\Collections\ArrayCollection;
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
     * @var array
     */
    protected static $mainMenuCategories = [];

    /**
     * @var ArrayCollection
     */
    protected static $parentCategories = [];

    /**
     * @var array
     */
    protected static $menuItems = [
        'catalogPage' => 'Каталог',
        'about_page' => 'О проекте',
        'shipping_page' => 'Доставка и возврат',
        'sitemap_page' => 'Карта сайта',
    ];

    /**
     * @var string
     */
    protected static $canonicalLink;

    public function __construct(EntityManager $entityManager)
    {
        self::$em = $entityManager;
        self::$em->getConnection()->getConfiguration()->setSQLLogger(null);
        self::$parentCategories = self::$em
            ->getRepository('AppBundle:Category')
            ->findBy([
                'isActive' => true,
                'parentCategory' => null,
            ]);
        $mainMenuCategories = self::$em
            ->getRepository('AppBundle:Category')
            ->findBy([
                'isActive' => true,
                'inMainMenu' => true,
            ]);
        foreach ($mainMenuCategories as $mainMenuCategory) {
            self::$mainMenuCategories[] = [
                'title' => $mainMenuCategory->getTitle(),
                'link' => $this->getParentCategoriesUrl($mainMenuCategory) . $mainMenuCategory->getAlias(),
            ];
        }
    }

    /**
     * @Route("/", name="homepage",
     *      options={"sitemap" = true})
     */
    public function indexAction(Request $request)
    {
        return $this->defaultRender('AppBundle:look4wear:index.html.twig');
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

        return $this->defaultRender('AppBundle:look4wear:vendor.html.twig', [
            'goods' => $goods,
            'totalCount' => $totalCount,
            ]);
    }

    /**
     * @Route("/catalog/{token}/brand/{vendorAlias}", name="filter", requirements={"token"=".+"})
     * @param $token
     * @param $vendorAlias
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function filterAction($token, $vendorAlias, Request $request)
    {
        $category = null;
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
            $category = end($categories);
        }
        $matches = [];
        $totalCount = 0;
        /** @var Vendor $category */
        $vendor = self::$em
            ->getRepository('AppBundle:Vendor')
            ->findOneBy([
                'alias' => $vendorAlias
            ]);
        if ($category && !$vendor) {
            return $this->redirectToRoute('catalog', [
                'token' => $token,
            ], 301);
        }
        $parentsUrl = null;
        if ($category) {
            $excludeWords = explode(';', $category->getExcludeWords());
            $excludeWords = array_filter($excludeWords);
            $searchString = $category->getSearchString();
            if ($vendor) {
                $searchString .= ' @vendorAlias =' . $vendorAlias;
//                $searchString .= ' ' . $vendorAlias;
                $seoText = self::$em
                    ->getRepository('AppBundle:SeoText')
                    ->findOneBy([
                        'alias' => $categoryAlias . '/' . $vendorAlias
                    ]);
                if ($seoText) {
                    self::$seoDescription = $seoText->getText();
                }
            }
            if ($excludeWords) {
                $searchString .= ' -' . implode(' -', $excludeWords);
            }
            $searchGoods = $this->searchByString($searchString, $request->query->getInt('page', 0));
            if (isset($searchGoods['matches'])) {
                $matches = $searchGoods['matches'];
            }
            $totalCount = $searchGoods['total_found'];
            $parentsUrl = $this->getParentCategoriesUrl($category);
            $actualUrl = $parentsUrl . $category->getAlias();
            if ($request->query->getInt('page', 0)) {
                self::$canonicalLink = $actualUrl . '/brand/' . $vendor->getAlias();
            }
            $pagination = [
                'url' => $actualUrl . '/brand/' . $vendor->getAlias() . '?',
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
                    if ($categoryTopVendor) {
                        $imgUrl = '';
                        if (file_exists($this->get('kernel')->getRootDir() . '/../web/media/brands/' . $categoryTopVendorAlias . '.png')) {
                            $imgUrl = $categoryTopVendorAlias . '.png';
                        }
                        $categoryTopVendorsResult[$categoryTopVendorAlias] = [
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
            $breadcrumbs = $this->getBreadcrumbs($category, $vendor);
        } else {
            $breadcrumbs = $this->getBreadcrumbs($category);
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
                if (isset($categoryItem->getData()['topVendors'][$vendorAlias])) {
                    $otherCategories[$categoryItem->getAlias()] = [
                        'title' => $categoryItem->getTitle(),
                        'count' => $categoryItem->getData()['topVendors'][$vendorAlias],
                        'link' => $this->getParentCategoriesUrl($categoryItem) . $categoryItem->getAlias(),
                    ];
                    if ($vendor) {
                        $otherCategories[$categoryItem->getAlias()]['vendorName'] = $vendor->getName();
                    }
                }
            }
        }
        arsort($otherCategories);
        $otherCategories = array_slice($otherCategories, 0, 20);
        if ($category->getChildrenCategories()) {
            foreach ($category->getChildrenCategories() as $childrenCategory) {
                $menuCategories[] = $childrenCategory;
            }
        }
        $menuCategories[] = $category;
        if ($category->getParentCategory() && $category->getParentCategory()->getChildrenCategories()) {
            foreach ($category->getParentCategory()->getChildrenCategories() as $brotherCategory) {
                $menuCategories[] = $brotherCategory;
            }
        }
        $menuCategories[] = $parentCategory = $category->getParentCategory();
        while ($parentCategory && $parentCategory->getParentCategory()) {
            $menuCategories[] = $parentCategory = $parentCategory->getParentCategory();
        }
        $menuCategories = array_merge($menuCategories, self::$parentCategories);

        return $this->defaultRender('AppBundle:look4wear:filter.html.twig', [
            'breadcrumbs' => $breadcrumbs,
            'goods' => $matches,
            'totalCount' => $totalCount,
            'seoTitle' => self::$seoTitle,
            'pageTitle' => self::$pageTitle,
            'pagination' => $pagination,
            'actualCategory' => $category,
            'actualParentCategories' => $menuCategories,
            'categoryTopVendorsResult' => $categoryTopVendorsResult,
            'otherCategories' => $otherCategories,
            'vendorAlias' => $vendorAlias,
            'parentsUrl' => $parentsUrl,
            'seoDescription' => self::$seoDescription,
            'canonicalLink' => self::$canonicalLink,
            'vendor' => $vendor,
            'keywords' => $category->getKeywords() ? $category->getKeywords() . ' ' . $vendor->getName() : $category->getTitle() . ' ' . $vendor->getName(),
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
        $pagination = null;
        if ($actualCategory) {
            $excludeWords = explode(';', $actualCategory->getExcludeWords());
            $excludeWords = array_filter($excludeWords);
            $searchString = $actualCategory->getSearchString();
            if ($excludeWords) {
                $searchString .= ' -' . implode(' -', $excludeWords);
            }
            $searchGoods = $this->searchByString($searchString, $request->query->getInt('page', 0));
            if (isset($searchGoods['matches'])) {
                $matches = $searchGoods['matches'];
            }
            $totalCount = $searchGoods['total_found'];
            $parentsUrl = $this->getParentCategoriesUrl($actualCategory);
            $actualUrl = $parentsUrl . $actualCategory->getAlias();
            if ($request->query->getInt('page', 0)) {
                self::$canonicalLink = $actualUrl;
            }
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
                    $categoryTopVendorsResult[$categoryTopVendorAlias] = [
                        'alias' => $categoryTopVendorAlias,
                        'name' => $categoryTopVendor->getName(),
                        'count' => $categoryTopVendorCount,
                        'imgUrl' => $imgUrl,
                    ];
                }
            }
        }
        if ($actualCategory->getChildrenCategories()) {
            foreach ($actualCategory->getChildrenCategories() as $childrenCategory) {
                $menuCategories[] = $childrenCategory;
            }
        }
        $menuCategories[] = $actualCategory;
        if ($actualCategory->getParentCategory() && $actualCategory->getParentCategory()->getChildrenCategories()) {
            foreach ($actualCategory->getParentCategory()->getChildrenCategories() as $brotherCategory) {
                $menuCategories[] = $brotherCategory;
            }
        }
        $menuCategories[] = $parentCategory = $actualCategory->getParentCategory();
        while ($parentCategory && $parentCategory->getParentCategory()) {
            $menuCategories[] = $parentCategory = $parentCategory->getParentCategory();
        }
        $menuCategories = array_merge($menuCategories, self::$parentCategories);
        return $this->defaultRender('AppBundle:look4wear:category.html.twig', [
            'breadcrumbs' => $this->getBreadcrumbs($actualCategory),
            'actualCategory' => $actualCategory,
            'actualParentCategories' => $menuCategories,
            'categoryTopVendorsResult' => $categoryTopVendorsResult,
            'goods' => $matches,
            'totalCount' => $totalCount,
            'pageTitle' => $actualCategory->getTitle(),
            'seoTitle' => $actualCategory->getSeoTitle(),
            'seoDescription' => $actualCategory->getDescription() ? $actualCategory->getDescription() : $actualCategory->getSeoTitle(),
            'pagination' => $pagination,
            'parentsUrl' => $parentsUrl,
            'actualUrl' => $actualUrl,
            'canonicalLink' => self::$canonicalLink,
            'keywords' => $actualCategory->getKeywords() ?: $actualCategory->getTitle(),
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
            if ($excludeWords) {
                $searchString .= ' -' . implode(' -', $excludeWords);
            }
            $searchGoods = $this->searchByStringAndLimit($searchString, 5);
            if (isset($searchGoods['matches'])) {
                if ($searchGoods['total_found'] >= 5) {
                    $categoryImage = json_decode(end($searchGoods['matches'])['attrs']['pictures'])[0];
                    $catalogCategories[] = [
                        'category' => $category,
                        'image' => $categoryImage,
                        'url' => '/catalog/' . $category->getAlias(),
                    ];
                }
            }
        }

        return $this->defaultRender('AppBundle:look4wear:catalog.html.twig', [
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
        $categories = self::$em
            ->getRepository('AppBundle:Category')
            ->findBy([
                'isActive' => true,
                'parentCategory' => null,
            ]);
        $vendors = self::$em
            ->getRepository('AppBundle:Vendor')
            ->findAll();
        $allVendors = [];
        foreach ($vendors as $vendor) {
            $allVendors[$vendor->getAlias()] = $vendor;
        }

        return $this->defaultRender('AppBundle:look4wear:sitemap.html.twig', [
            'seoTitle' => 'Карта сайта look4wear.ru',
            'pageTitle' => self::$pageTitle,
            'seoDescription' => 'Карта сайта look4wear.ru',
            'parentCategories' => $categories,
            'allVendors' => $allVendors,
        ]);
    }

    /**
     * @Route("/filter/{categoryAlias}/{vendorAlias}", name="filter_old")
     * @param $categoryAlias
     * @param $vendorAlias
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function filterOldAction($categoryAlias, $vendorAlias, Request $request)
    {

        /** @var Category $category */
        $category = self::$em
            ->getRepository('AppBundle:Category')
            ->findOneBy([
                'alias' => $categoryAlias
            ]);
        return $this->redirectToRoute('filter', [
            'token' => $this->getParentCategoriesUrl($category, false) . $category->getAlias(),
            'vendorAlias' => $vendorAlias,
        ], 301);
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

        return $this->defaultRender('AppBundle:look4wear:search.html.twig', [
            'goods' => $matches,
            'seoTitle' => '',
            'pageTitle' => '',
            'childrenCategories' => [],
            'actualParentCategories' => self::$parentCategories,
            'actualCategory' => null,
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

        return $this->defaultRender('AppBundle:look4wear:about.html.twig', [
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

        return $this->defaultRender('AppBundle:look4wear:shipping.html.twig', [
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

        return $this->defaultRender('AppBundle:look4wear:buy.html.twig', [
            'seoTitle' => 'Осуществляется переход в магазин ' . $goods->getOffer()->getName(),
            'pageTitle' => 'Осуществляется переход в магазин ' . $goods->getOffer()->getName(),
            'seoDescription' => 'Осуществляется переход в магазин ' . $goods->getOffer()->getName(),
            'childrenCategories' => [],
            'goods' => $goods,
        ]);
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
     * @param Vendor $vendor
     * @return array
     */
    private function getBreadcrumbs(Category $category, Vendor $vendor = null)
    {
        if ($vendor) {
            $breadcrumbs[] = [
                'link' => '',
                'title' => $category->getTitle() . ' "' . $vendor->getName() . '"',
            ];
            $breadcrumbs[] = [
                'link' => $this->getParentCategoriesUrl($category) . $category->getAlias(),
                'title' => $category->getName(),
                'seoTitle' => $category->getSeoTitle(),
            ];
        } else {
            $breadcrumbs[] = [
                'link' => '',
                'title' => $category->getName(),
                'seoTitle' => $category->getSeoTitle(),
            ];
        }
        $parentCategory = $category->getParentCategory();
        while ($parentCategory) {
            $breadcrumbs[] = [
                'link' => $this->getParentCategoriesUrl($parentCategory) . $parentCategory->getAlias(),
                'title' => $parentCategory->getName(),
                'seoTitle' => $parentCategory->getSeoTitle(),
            ];
            $parentCategory = $parentCategory->getParentCategory();
        }
        $breadcrumbs[] = [
            'link' => '/',
            'title' => 'Главная',
            'seoTitle' => 'Одежда для всей семьи по выгодным ценам.',
        ];

        return array_reverse($breadcrumbs);
    }

    /**
     * @param Category $category
     * @param boolean $withCatalog
     * @return string
     */
    private function getParentCategoriesUrl(Category $category, $withCatalog = true)
    {
        $parentCategories = [];
        if ($withCatalog) {
            $parentsUrl = '/catalog/';
        } else {
            $parentsUrl = '';
        }
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

    private function defaultRender($templateName, $templateArgs = [])
    {
        return $this->render($templateName, [
            'mainMenuCategories' => self::$mainMenuCategories,
            'parentCategories' => self::$parentCategories,
            'menuItems' => self::$menuItems,
        ] + $templateArgs);
    }
}
