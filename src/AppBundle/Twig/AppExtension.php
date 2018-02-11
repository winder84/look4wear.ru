<?php
namespace AppBundle\Twig;

use AppBundle\Entity\Category;

class AppExtension extends \Twig_Extension
{
    public function getFilters()
    {
        return array(
            new \Twig_SimpleFilter('my_replace', array($this, 'twigReplace')),
            new \Twig_SimpleFilter('json_decode', array($this, 'twigJSONDecode')),
            new \Twig_SimpleFilter('get_category_url', array($this, 'getCategoryUrl')),
        );
    }

    public function twigReplace($string)
    {
        $result = preg_replace('/\?.*?$/', '', $string);

        return $result;
    }

    public function twigJSONDecode($string)
    {

        return json_decode($string);
    }

    /**
     * @param Category $category
     * @return string
     */
    public function getCategoryUrl(Category $category)
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

        return $parentsUrl . $category->getAlias();
    }
}