<?php

namespace AppBundle\Controller;

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
     * @Route("/vendor/{alias}", name="vendors")
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
        $sphinxSearch = $this->get('iakumai.sphinxsearch.search');
        $sphinxSearch->setLimits($page * self::$resultsOnPage, self::$resultsOnPage);
        $sphinxSearch->SetMatchMode(SPH_MATCH_EXTENDED);
        $searchGoods = $sphinxSearch->query($searchString);
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
}
