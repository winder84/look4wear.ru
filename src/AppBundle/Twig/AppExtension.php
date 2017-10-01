<?php
namespace AppBundle\Twig;

class AppExtension extends \Twig_Extension
{
    public function getFilters()
    {
        return array(
            new \Twig_SimpleFilter('my_replace', array($this, 'twigReplace')),
        );
    }

    public function twigReplace($string)
    {
        $result = preg_replace('/\?.*?$/', '', $string);

        return $result;
    }
}