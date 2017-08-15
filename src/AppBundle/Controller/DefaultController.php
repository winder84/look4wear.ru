<?php

namespace AppBundle\Controller;

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
    protected static $resultsOnPage = 30;

    /**
     * @Route("/", name="homepage")
     */
    public function indexAction(Request $request)
    {
        // replace this example code with whatever you need
        return $this->render('AppBundle:look4wear:index.html.twig', array(
            'base_dir' => realpath($this->container->getParameter('kernel.root_dir').'/..').DIRECTORY_SEPARATOR,
        ));
    }

    /**
     * @Route("/search/{search}/{page}", name="search_page")
     * @param $search
     * @param $page
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function searchAction($search, $page = 0)
    {
        $matches = [];
        self::$em = $this->getDoctrine()->getManager();
        self::$em->getConnection()->getConfiguration()->setSQLLogger(null);
        $sphinxSearch = $this->get('iakumai.sphinxsearch.search');
        $sphinxSearch->setLimits($page * self::$resultsOnPage, self::$resultsOnPage);
        $searchGoods = $sphinxSearch->search($search, ['goods']);
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

        var_dump($totalCount);
        return $this->render('AppBundle:look4wear:index.html.twig', [
            'goods' => $goods,
            'totalCount' => $totalCount,
        ]);
    }
}
