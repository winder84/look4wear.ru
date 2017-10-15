<?php
namespace AppBundle\Twig;

class AppExtension extends \Twig_Extension
{
    public function getFilters()
    {
        return array(
            new \Twig_SimpleFilter('my_replace', array($this, 'twigReplace')),
            new \Twig_SimpleFilter('json_decode', array($this, 'twigJSONDecode')),
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
}