<?php

namespace AppBundle\EventListener;

use AppBundle\Entity\Category;
use AppBundle\Entity\Vendor;
use Doctrine\Common\Persistence\ManagerRegistry;
use IAkumaI\SphinxsearchBundle\Search\Sphinxsearch;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Presta\SitemapBundle\Event\SitemapPopulateEvent;
use Presta\SitemapBundle\Service\UrlContainerInterface;
use Presta\SitemapBundle\Sitemap\Url\UrlConcrete;

class SitemapSubscriber implements EventSubscriberInterface
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

    /**
     * @inheritdoc
     */
    public static function getSubscribedEvents()
    {
        return [
            SitemapPopulateEvent::ON_SITEMAP_POPULATE => 'populate',
        ];
    }

    /**
     * @param SitemapPopulateEvent $event
     */
    public function populate(SitemapPopulateEvent $event): void
    {
        $this->registerBlogPostsUrls($event->getUrlContainer());
    }

    /**
     * @param UrlContainerInterface $urls
     */
    public function registerBlogPostsUrls(UrlContainerInterface $urls): void
    {
        $categories = $this->doctrine->getRepository(Category::class)->findBy([
            'isActive' => true
        ]);
        $vendors = $this->doctrine->getRepository(Vendor::class)->findAll();
        foreach ($categories as $category) {
            $categoryUrl = self::getParentCategoriesUrl($category) . $category->getAlias();
            $urls->addUrl(
                new UrlConcrete(
                    $this->urlGenerator->generate(
                        'catalog',
                        ['token' => $categoryUrl],
                        UrlGeneratorInterface::ABSOLUTE_URL
                    )
                ),
                'catalog'
            );
            if ($category->getParentCategory()) {
                if (isset($category->getData()['topVendors'])) {
                    $categoryVendors = $category->getData()['topVendors'];
                    foreach ($categoryVendors as $categoryVendor => $categoryVendorCount) {
                            $urls->addUrl(
                                new UrlConcrete(
                                    $this->urlGenerator->generate(
                                        'filter',
                                        ['categoryAlias' => $category->getAlias(), 'vendorAlias' => $categoryVendor],
                                        UrlGeneratorInterface::ABSOLUTE_URL
                                    )
                                ),
                                'filters'
                            );
                    }
                }
            }
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

    /**
     * @param $searchString
     * @param $limit
     * @return mixed
     */
    public function searchByStringAndLimit($searchString, $limit)
    {
        $sphinxSearch = new Sphinxsearch('localhost', '9312');
        $sphinxSearch->setLimits(0, $limit, 100000);
        $sphinxSearch->SetMatchMode(SPH_MATCH_EXTENDED);
        return $sphinxSearch->query($searchString, 'goods', false);
    }
}