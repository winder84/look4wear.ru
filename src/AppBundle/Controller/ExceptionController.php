<?php

namespace AppBundle\Controller;


use AppBundle\Entity\Category;
use Doctrine\ORM\EntityManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class ExceptionController extends Controller
{

    /**
     * @var EntityManager
     */
    protected static $em;

    /**
     * @var array
     */
    protected static $mainMenuCategories = [];

    /**
     * @var array
     */
    protected static $menuItems = [
        'catalogPage' => 'Каталог',
        'about_page' => 'О проекте',
        'sitemap_page' => 'Карта сайта',
    ];

    /**
     * @Route("/404")
     */
    public function show404Action()
    {
        self::$em = $this->getDoctrine()->getManager();
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
        return $this->render('AppBundle:Exception:show404.html.twig', [
            'mainMenuCategories' => self::$mainMenuCategories,
            'menuItems' => self::$menuItems,
        ]);
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