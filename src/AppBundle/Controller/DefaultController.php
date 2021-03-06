<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Category;
use AppBundle\Entity\GoodsStat;
use AppBundle\Entity\Vendor;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class DefaultController extends Controller
{
    /**
     * @var EntityManager
     */
    protected static $em;

    /**
     * @var int
     */
    protected static $resultsOnPage = 18;

    /**
     * @var int
     */
    protected static $topVendorsOnPage = 12;

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
     * @var ArrayCollection
     */
    protected static $lastArticles = [];

    /**
     * @var ArrayCollection
     */
    protected static $shops = [];

    /**
     * @var array
     */
    protected static $menuItems = [
        'about_page' => 'О проекте',
        'shipping_page' => 'Доставка и возврат',
        'sitemap_page' => 'Карта сайта',
    ];

    /**
     * @var string
     */
    protected static $canonicalLink;

    /**
     * @var array
     */
    protected static $firstDescriptionArray = [
        'Одежда для всей семьи в интернет-магазине.',
        'Каталог look4wear.ru, одежда для мужчин и женщин.',
        'Интернет-магазин одежды и обуви.',
        'Каталог современной обуви и одежды look4wear.ru.',
        'Интернет-магазин современной и модной одежды.',
        'Интернет-магазин модной одежды и обуви.',
        'Каталог одежды из разных интернет-магазинов.',
        'Собрание интернет-магазинов одежды и обуви для детей и взрослых.',
        'Последние коллекции одежды для семьи.',
        'Одежда качественных брендов в интернет-магазине.',
    ];

    /**
     * @var array
     */
    protected static $secondDescriptionArray = [
        'Купоны, выгодные цены и акции от интернет-магазинов.',
        'Доставка по территории СНГ. Выгодные цены и акции.',
        'Заманчивые предложения, купоны и доставка по РФ.',
        'Доставка по России и СНГ. Качественные товары из интернет-мазанов.',
        'Интересные предложения и выгодные акции от ведущих интернет-магазинов одежды.',
        'Широкий ассортимент акционных товаров. Доставка по РФ.',
        'Бесплатная доставка по РФ. Большой выбор товаров.',
        'Цены на одежду популярных интернет-магазинов.',
        'Характеристики и отзывы на одежду и обувь.',
        'Без переплат и очередей.',
    ];

    /**
     * @var array
     */
    protected static $seoTitles = [
        'Купить %s недорого – фото, цена, характеристики.',
        '%s купить в России с бесплатной доставкой!',
        'Купить %s – в интернет-магазине look4wear.ru',
        'Заказать %s с доставкой в день заказа!',
        'Заказать %s в России - низкие цены, доставка.',
        '%s - купить по выгодной цене. Бесплатная доставка.',
        '%s - широкий ассортимент, успейте заказать!',
        '%s по низким ценам: широкий выбор, акции, доставка.',
        'Купить %s в интернет-магазине по низким ценам.',
        'Купить %s в каталоге одежды и обуви look4wear.ru.',
    ];

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
        self::$lastArticles = self::$em
            ->getRepository('AppBundle:Article')
            ->findBy([], ['id' => 'DESC'], 5);
        self::$shops = self::$em
            ->getRepository('AppBundle:Offer')
            ->findBy(['isDelete' => false]);
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
        $category = $this->getActualCategoryByToken($token);
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
                        'alias' => $category->getAlias() . '/' . $vendorAlias
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
            $pagination = [
                'url' => $actualUrl . '/brand/' . $vendor->getAlias() . '?',
                'currentPage' => $request->query->getInt('page', 1),
                'totalPagesCount' => ceil($totalCount / self::$resultsOnPage),
            ];
            $categoryTopVendors = [];
            if (isset($category->getData()['topVendors'])) {
                $categoryTopVendors = $category->getData()['topVendors'];
            }
            $categoryTopVendorsResult = $this->getCategoryTopVendors($categoryTopVendors, $vendor);
        }
        if ($category && $vendor) {
            $vendorLastNumber = substr($vendor->getId(), 0, 1);
            self::$seoTitle = sprintf(self::$seoTitles[$vendorLastNumber], mb_strtolower($category->getTitle() . ' ' . $vendor->getName(), 'UTF-8'));
            self::$pageTitle = ucfirst($category->getTitle()) . ' ' . $vendor->getName();
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
        $link = $this->generateUrl(
            'filter', [
            'token' => $token,
            'vendorAlias' => $vendorAlias,
        ],
            UrlGeneratorInterface::ABSOLUTE_URL
        );
        if ($link != $request->getUri()) {
            self::$canonicalLink = $link;
        }

        $page = $request->query->getInt('page', 0);
        return $this->defaultRender('AppBundle:look4wear:filter.html.twig', [
            'breadcrumbs' => $breadcrumbs,
            'goods' => $matches,
            'totalCount' => $totalCount,
            'seoTitle' => self::$seoTitle . ($page ? ' - стр.' . $page : ''),
            'pageTitle' => self::$pageTitle,
            'pagination' => $pagination,
            'actualCategory' => $category,
            'actualParentCategories' => $menuCategories,
            'categoryTopVendorsResult' => $categoryTopVendorsResult,
            'otherCategories' => $otherCategories,
            'vendorAlias' => $vendorAlias,
            'parentsUrl' => $parentsUrl,
            'seoDescription' => self::$seoDescription ?: $this->getDefaultDescription($category, $vendor),
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
        $actualCategory = $this->getActualCategoryByToken($token);
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
            $pagination = [
                'url' => $actualUrl . '?',
                'currentPage' => $request->query->getInt('page', 1),
                'totalPagesCount' => ceil($totalCount / self::$resultsOnPage),
            ];
        }
        $categoryTopVendors = [];
        if (isset($actualCategory->getData()['topVendors'])) {
            $categoryTopVendors = $actualCategory->getData()['topVendors'];
        }
        $categoryTopVendorsResult = $this->getCategoryTopVendors($categoryTopVendors);
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
        $link = $this->generateUrl(
            'catalog', [
            'token' => $token
        ],
            UrlGeneratorInterface::ABSOLUTE_URL
        );
        if ($link != $request->getUri()) {
            self::$canonicalLink = $link;
        }
        $page = $request->query->getInt('page', 0);
        return $this->defaultRender('AppBundle:look4wear:category.html.twig', [
            'breadcrumbs' => $this->getBreadcrumbs($actualCategory),
            'actualCategory' => $actualCategory,
            'actualParentCategories' => $menuCategories,
            'categoryTopVendorsResult' => $categoryTopVendorsResult,
            'goods' => $matches,
            'totalCount' => $totalCount,
            'pageTitle' => $actualCategory->getTitle(),
            'seoTitle' => $actualCategory->getSeoTitle() . ($page ? ' - стр.' . $page : ''),
            'seoDescription' => $actualCategory->getDescription() ?: $this->getDefaultDescription($actualCategory),
            'pagination' => $pagination,
            'parentsUrl' => $parentsUrl,
            'actualUrl' => $actualUrl,
            'canonicalLink' => self::$canonicalLink,
            'keywords' => $actualCategory->getKeywords() ?: $actualCategory->getTitle(),
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
            'actualCategory' => null,
            'actualParentCategories' => self::$parentCategories,
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
     *
     * @param $alias
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function productBuyAction($alias, Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $goods = $em
            ->getRepository('AppBundle:Goods')
            ->findOneBy(array('alias' => $alias));
        if (!$goods) {
            throw $this->createNotFoundException('The $goods does not exist');
        }

        $this->saveGoodsStat($goods->getId(), $request);
        return $this->defaultRender('AppBundle:look4wear:buy.html.twig', [
            'seoTitle' => 'Осуществляется переход в магазин ' . $goods->getOffer()->getName(),
            'pageTitle' => 'Осуществляется переход в магазин ' . $goods->getOffer()->getName(),
            'seoDescription' => 'Осуществляется переход в магазин ' . $goods->getOffer()->getName(),
            'childrenCategories' => [],
            'goods' => $goods,
        ]);
    }

    /**
     * @Route("/article/{alias}", name="article_page")
     */
    public function articleAction($alias)
    {
        $em = $this->getDoctrine()->getManager();
        $article = $em
            ->getRepository('AppBundle:Article')
            ->findOneBy(['alias' => $alias]);
        if (!$article) {
            throw $this->createNotFoundException('The $article does not exist');
        }

        $articleText = $article->getText();

        preg_match_all('%\[category\:(.*?)\]%', $articleText, $matches);
        if (isset($matches[1])) {
            foreach ($matches[1] as $blockInsertParams) {
                list($categoryId, $categoryCount) = explode(',', $blockInsertParams);
                $category = $em
                    ->getRepository('AppBundle:Category')
                    ->findOneBy(['id' => $categoryId]);
                if ($category) {
                    $excludeWords = explode(';', $category->getExcludeWords());
                    $excludeWords = array_filter($excludeWords);
                    $searchString = $category->getSearchString();
                    if ($excludeWords) {
                        $searchString .= ' -' . implode(' -', $excludeWords);
                    }
                    $searchGoods = $this->searchByStringAndLimit($searchString, (int)$categoryCount);
                    if (isset($searchGoods['matches'])) {
                        $goods = $searchGoods['matches'];
                    }
                    $parentsUrl = $this->getParentCategoriesUrl($category);
                    $actualUrl = $parentsUrl . $category->getAlias();
                    $newBlock = '<p style="text-align: center;"><a target="_blank" class="productLink" href="' . $actualUrl .'">' . $category->getTitle() . '</a></p>';
                    $newBlock .= $this->render('AppBundle:look4wear:goods.block.html.twig', ['goods' => $goods])->getContent();
                    $articleText = str_replace('[category:' . $blockInsertParams . ']', $newBlock, $articleText);
                    $article->setText($articleText);
                }
            }
        }

        return $this->defaultRender('AppBundle:look4wear:article.html.twig', [
            'seoTitle' => $article->getSeoTitle(),
            'seoDescription' => $article->getSeoDescription(),
            'article' => $article,
        ]);
    }

    /**
     * @Route("/shop/{alias}", name="shop_page")
     */
    public function shopAction($alias, Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $offer = $em
            ->getRepository('AppBundle:Offer')
            ->findOneBy(['alias' => $alias]);
        if (!$offer) {
            throw $this->createNotFoundException('The $offer does not exist');
        }

        $matches = [];
        $pagination = null;
        $searchString = '@offerAlias ' . $alias ;
        $searchGoods = $this->searchByString($searchString, $request->query->getInt('page', 0));
        if (isset($searchGoods['matches'])) {
            $matches = $searchGoods['matches'];
        }
        $totalCount = $searchGoods['total_found'];
        $actualUrl = '/shop/' . $alias;
        $pagination = [
            'url' => $actualUrl . '?',
            'currentPage' => $request->query->getInt('page', 1),
            'totalPagesCount' => ceil($totalCount / self::$resultsOnPage),
        ];
        $link = $this->generateUrl(
            'shop_page', [
            'alias' => $alias,
        ],
            UrlGeneratorInterface::ABSOLUTE_URL
        );
        if ($link != $request->getUri()) {
            self::$canonicalLink = $link;
        }

        return $this->defaultRender('AppBundle:look4wear:offer.html.twig', [
            'seoTitle' => 'Купить современную одежду в ' . $offer->getName() . '. Акционные товары, скидки и доставка по России.',
            'offer' => $offer,
            'goods' => $matches,
            'pagination' => $pagination,
            'canonicalLink' => self::$canonicalLink,
            'actualCategory' => null,
            'actualParentCategories' => self::$parentCategories,
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

    /**
     * @param $topVendors
     * @param Vendor|null $vendor
     * @return array
     */
    private function getCategoryTopVendors($topVendors, Vendor $vendor = null)
    {
        $categoryTopVendorsResult = [];
        if ($topVendors) {
            $topVendors = array_slice($topVendors, 0, self::$topVendorsOnPage + 1);
            $categoryTopVendors = self::$em
                ->getRepository('AppBundle:Vendor')
                ->findBy([
                    'alias' => array_keys($topVendors)
                ]);
            foreach ($categoryTopVendors as $categoryTopVendor) {
                if ($categoryTopVendor != $vendor) {
                    $imgUrl = '';
                    if (file_exists($this->get('kernel')->getRootDir() . '/../web/media/brands/' . $categoryTopVendor->getAlias() . '.png')) {
                        $imgUrl = $categoryTopVendor->getAlias() . '.png';
                    }
                    $categoryTopVendorsResult[$categoryTopVendor->getAlias()] = [
                        'alias' => $categoryTopVendor->getAlias(),
                        'name' => $categoryTopVendor->getName(),
                        'count' => $topVendors[$categoryTopVendor->getAlias()],
                        'imgUrl' => $imgUrl,
                    ];
                }
            }
        }

        return $categoryTopVendorsResult;
    }

    /**
     * @param $token
     * @return mixed|null
     */
    private function getActualCategoryByToken($token)
    {
        $categoryAliases = explode('/', $token);
        $categories = [];
        $actualCategory = null;
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

        return $actualCategory;
    }

    private function defaultRender($templateName, $templateArgs = [])
    {
        return $this->render($templateName, [
            'mainMenuCategories' => self::$mainMenuCategories,
            'parentCategories' => self::$parentCategories,
            'menuItems' => self::$menuItems,
            'lastArticles' => self::$lastArticles,
            'shops' => self::$shops,
        ] + $templateArgs);
    }

    private function getDefaultDescription(Category $category, Vendor $vendor = null)
    {
        $obj = $vendor ?: $category;
        $firstNumber = substr($obj->getId(), 0, 1);
        $lastNumber = substr($obj->getId(), -1, 1);

        return self::$firstDescriptionArray[$firstNumber] . $category->getTitle() . ($vendor ? ' ' . $vendor->getName() : '') . '.' . self::$secondDescriptionArray[$lastNumber];
    }

    private function saveGoodsStat($goodsId, Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $newGoodsStat = new GoodsStat();
        $newGoodsStat->setClientIp($request->getClientIp());
        $newGoodsStat->setClientUserAgent($request->headers->get('User-Agent'));
        $newGoodsStat->setGoodsId($goodsId);
        $em->persist($newGoodsStat);
        $em->flush();
    }
}
