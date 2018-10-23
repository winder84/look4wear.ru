<?php

namespace AppBundle\EventListener;

use AppBundle\Entity\Article;
use AppBundle\Entity\Category;
use JantaoDev\SitemapBundle\Service\SitemapListenerInterface;
use JantaoDev\SitemapBundle\Event\SitemapGenerateEvent;
use JantaoDev\SitemapBundle\Sitemap\Url;
use Doctrine\Common\Persistence\ManagerRegistry;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class SitemapListener implements SitemapListenerInterface
{
    /**
     * @var UrlGeneratorInterface
     */
    private $urlGenerator;

    /**
     * @var ManagerRegistry
     */
    private $doctrine;

    /**
     * @param UrlGeneratorInterface $urlGenerator
     * @param ManagerRegistry       $doctrine
     */
    public function __construct(UrlGeneratorInterface $urlGenerator, ManagerRegistry $doctrine)
    {
        $this->urlGenerator = $urlGenerator;
        $this->doctrine = $doctrine;
    }

    public function generateSitemap(SitemapGenerateEvent $event)
    {

        $sitemap = $event->getSitemap();
        $categories = $this->doctrine->getRepository(Category::class)->findBy([
            'isActive' => true
        ]);
        foreach ($categories as $category) {
            $categoryUrl = self::getParentCategoriesUrl($category) . $category->getAlias();
            $url = new Url($this->urlGenerator->generate(
                'catalog',
                ['token' => $categoryUrl],
                UrlGeneratorInterface::ABSOLUTE_URL
            ), new \DateTime('now'), 0.8, 'daily');
            $sitemap->add($url);
            if ($category->getParentCategory()) {
                if (isset($category->getData()['topVendors'])) {
                    $categoryVendors = $category->getData()['topVendors'];
                    foreach ($categoryVendors as $categoryVendor => $categoryVendorCount) {
                        $url = new Url($this->urlGenerator->generate(
                            'filter',
                            ['token' => $categoryUrl, 'vendorAlias' => $categoryVendor],
                            UrlGeneratorInterface::ABSOLUTE_URL
                        ), new \DateTime('now'), 0.8, 'daily');
                        $sitemap->add($url);
                    }
                }
            }
        }
        $articles = $this->doctrine->getRepository(Article::class)->findBy([]);
        foreach ($articles as $article) {
            $url = new Url($this->urlGenerator->generate(
                'article_page',
                ['alias' => $article->getAlias()],
                UrlGeneratorInterface::ABSOLUTE_URL
            ), new \DateTime('now'), 0.8, 'daily');
            $sitemap->add($url);
        }

    }

    /**
     * @param Category $category
     * @return string
     */
    private function getParentCategoriesUrl(Category $category)
    {
        $parentCategories = [];
        $parentsUrl = '';
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