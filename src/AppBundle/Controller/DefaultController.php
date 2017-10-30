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
     * @Route("/", name="homepage")
     */
    public function indexAction(Request $request)
    {
        return $this->render('AppBundle:look4wear:index.html.twig', array(
            'base_dir' => realpath($this->container->getParameter('kernel.root_dir').'/..').DIRECTORY_SEPARATOR,
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
     * @Route("/category/{alias}", name="category")
     * @param $alias
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function categoryAction($alias, Request $request)
    {
        $matches = [];
        $totalCount = 0;
        $childrenCategories = [];
        $pagination = null;
        self::$em = $this->getDoctrine()->getManager();
        self::$em->getConnection()->getConfiguration()->setSQLLogger(null);
        /** @var Category $category */
        $category = self::$em
            ->getRepository('AppBundle:Category')
            ->findOneBy([
                'alias' => $alias
            ]);
        if ($category) {
            $excludeWords = explode(';', $category->getExcludeWords());
            $searchString = $category->getSearchString();
            if (array_filter($excludeWords)) {
                $searchString .= ' -' . implode(' -', $excludeWords);
            }
            $searchGoods = $this->searchByString($searchString, $request->query->getInt('page', 0));
            if (isset($searchGoods['matches'])) {
                $matches = $searchGoods['matches'];
            }
            $totalCount = $searchGoods['total_found'];
            if (count($category->getChildrenCategories()) > 0) {
                $childrenCategories = $category->getChildrenCategories();
            } else {
                $childrenCategories = $category->getParentCategory()->getChildrenCategories();
            }
            $pagination = [
                'url' => '/category/' . $alias . '?',
                'currentPage' => $request->query->getInt('page', 1),
                'totalPagesCount' => floor($totalCount / self::$resultsOnPage),
            ];
        }

        return $this->render('AppBundle:look4wear:category.html.twig', [
            'breadcrumbs' => $this->getBreadcrumbs($category),
            'category' => $category,
            'goods' => $matches,
            'totalCount' => $totalCount,
            'pageTitle' => $category->getTitle(),
            'seoTitle' => $category->getSeoTitle(),
            'childrenCategories' => $childrenCategories,
            'pagination' => $pagination,
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
        $pageTitle = '';
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
                $searchString .= ' ' . $vendor->getName();
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
        }
        if ($category && $vendor) {
            $pageTitle = ucfirst($category->getTitle()) . ' ' . $vendor->getName();
        }

        return $this->render('AppBundle:look4wear:filter.html.twig', [
            'goods' => $matches,
            'totalCount' => $totalCount,
            'seoTitle' => '',
            'pageTitle' => $pageTitle,
            'childrenCategories' => $childrenCategories,
            'pagination' => $pagination,
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
            'url' => '/search' . '?searchString=' .$searchString . '&',
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
     * @param Category $category
     * @return array
     */
    private function getBreadcrumbs(Category $category)
    {
        $breadcrumbs = [];
        $parentCategory = $category->getParentCategory();
        while ($parentCategory) {
            $breadcrumbs[] = ['link' => '/category/' . $parentCategory->getAlias(), 'title' => $parentCategory->getName()];
            $parentCategory = $parentCategory->getParentCategory();
        }
        $breadcrumbs[] = ['link' => '/', 'title' => 'Главная'];

        return array_reverse($breadcrumbs);
    }
}
