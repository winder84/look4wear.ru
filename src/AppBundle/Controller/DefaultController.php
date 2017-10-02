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
     */
    public function categoryAction($alias, Request $request)
    {
        $matches = [];
        $goods = [];
        $totalCount = 0;
        $page = $request->get('page');
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
            $searchGoods = $this->searchByString($searchString, $page);
            if (isset($searchGoods['matches'])) {
                $matches = $searchGoods['matches'];
            }
            $totalCount = $searchGoods['total_found'];
            $goodsIds = [];
            foreach ($matches as $matchesKey => $matchesItem) {
                $goodsIds[] = $matchesKey;
            }
            $qb = self::$em->createQueryBuilder();
            $qb->select('Goods')
                ->from('AppBundle:Goods', 'Goods')
                ->where('Goods.id IN (:goodsIds)')
                ->andWhere('Goods.isDelete = 0')
                ->setParameter('goodsIds', $goodsIds);
            $query = $qb->getQuery();
            $goods = $query->getResult();
        }

        return $this->render('AppBundle:look4wear:category.html.twig', [
            'goods' => $goods,
            'totalCount' => $totalCount,
        ]);
    }

    /**
     * @Route("/filter/{category}/{vendor}", name="filter")
     */
    public function filterAction($category, $vendor, Request $request)
    {
        $matches = [];
        $goods = [];
        $totalCount = 0;
        $page = $request->get('page');
        self::$em = $this->getDoctrine()->getManager();
        self::$em->getConnection()->getConfiguration()->setSQLLogger(null);
        /** @var Category $category */
        $category = self::$em
            ->getRepository('AppBundle:Category')
            ->findOneBy([
                'alias' => $category
            ]);
        /** @var Category $category */
        $vendor = self::$em
            ->getRepository('AppBundle:Vendor')
            ->findOneBy([
                'alias' => $vendor
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
            $searchGoods = $this->searchByString($searchString, $page);
            if (isset($searchGoods['matches'])) {
                $matches = $searchGoods['matches'];
            }
            $totalCount = $searchGoods['total_found'];
            $goodsIds = [];
            foreach ($matches as $matchesKey => $matchesItem) {
                $goodsIds[] = $matchesKey;
            }
            $qb = self::$em->createQueryBuilder();
            $qb->select('Goods')
                ->from('AppBundle:Goods', 'Goods')
                ->where('Goods.id IN (:goodsIds)')
                ->andWhere('Goods.isDelete = 0')
                ->setParameter('goodsIds', $goodsIds);
            $query = $qb->getQuery();
            $goods = $query->getResult();
        }

        return $this->render('AppBundle:look4wear:filter.html.twig', [
            'goods' => $goods,
            'totalCount' => $totalCount,
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
        $page = $request->get('page');
        self::$em = $this->getDoctrine()->getManager();
        self::$em->getConnection()->getConfiguration()->setSQLLogger(null);
        $searchGoods = $this->searchByString($searchString, $page);
        if (isset($searchGoods['matches'])) {
            $matches = $searchGoods['matches'];
        }
        $totalCount = $searchGoods['total_found'];
        $goodsIds = [];
        foreach ($matches as $matchesKey => $matchesItem) {
            $goodsIds[] = $matchesKey;
        }
        $qb = self::$em->createQueryBuilder();
        $qb->select('Goods')
            ->from('AppBundle:Goods', 'Goods')
            ->where('Goods.id IN (:goodsIds)')
            ->andWhere('Goods.isDelete = 0')
            ->setParameter('goodsIds', $goodsIds);
        $query = $qb->getQuery();
        $goods = $query->getResult();

        return $this->render('AppBundle:look4wear:search.html.twig', [
            'goods' => $goods,
            'totalCount' => $totalCount,
            'searchString' => $searchString,
        ]);
    }

    private function searchByString($searchString, $page)
    {
        $sphinxSearch = $this->get('iakumai.sphinxsearch.search');
        $sphinxSearch->setLimits($page * self::$resultsOnPage, self::$resultsOnPage, 1000000);
        $sphinxSearch->SetMatchMode(SPH_MATCH_EXTENDED);
        return $sphinxSearch->query($searchString);
    }
}
