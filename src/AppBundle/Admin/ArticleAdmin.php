<?php

namespace AppBundle\Admin;

use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Show\ShowMapper;
use Sonata\FormatterBundle\Form\Type\SimpleFormatterType;

class ArticleAdmin extends AbstractAdmin
{
    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper
            ->add('alias')
            ->add('title')
            ->add('seoTitle')
            ->add('seoDescription')
            ->add('text')
        ;
    }

    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->add('alias')
            ->add('title')
            ->add('seoTitle')
            ->add('_action', null, [
                'actions' => [
                    'show' => [],
                    'edit' => [],
                    'delete' => [],
                ],
            ])
        ;
    }

    protected function configureFormFields(FormMapper $formMapper)
    {
        $formMapper
            ->add('title')
            ->add('seoTitle')
            ->add('seoDescription')
            ->add('text', 'ckeditor', [
                'config' => [
                    'uiColor' => '#ffffff',
                    'height' => '500',
                ],
            ])
        ;
    }

    protected function configureShowFields(ShowMapper $showMapper)
    {
        $showMapper
            ->add('id')
            ->add('alias')
            ->add('title')
            ->add('seoTitle')
            ->add('seoDescription')
            ->add('text')
        ;
    }

    public function prePersist($article)
    {
        $article->setAlias(mb_substr(self::TransUrl($article->getTitle()), 0, 250,'UTF-8'));
    }

    /**
     * @param $str string
     * @return string
     */
    private static function TransUrl($str)
    {
        $tr = [
            "А" => "a",
            "Б" => "b",
            "В" => "v",
            "Г" => "g",
            "Д" => "d",
            "Е" => "e",
            "Ё" => "e",
            "Ж" => "j",
            "З" => "z",
            "И" => "i",
            "Й" => "y",
            "К" => "k",
            "Л" => "l",
            "М" => "m",
            "Н" => "n",
            "О" => "o",
            "П" => "p",
            "Р" => "r",
            "С" => "s",
            "Т" => "t",
            "У" => "u",
            "Ф" => "f",
            "Х" => "h",
            "Ц" => "ts",
            "Ч" => "ch",
            "Ш" => "sh",
            "Щ" => "sch",
            "Ъ" => "",
            "Ы" => "i",
            "Ь" => "j",
            "Э" => "e",
            "Ю" => "yu",
            "Я" => "ya",
            "а" => "a",
            "б" => "b",
            "в" => "v",
            "г" => "g",
            "д" => "d",
            "е" => "e",
            "ё" => "e",
            "ж" => "j",
            "з" => "z",
            "и" => "i",
            "й" => "y",
            "к" => "k",
            "л" => "l",
            "м" => "m",
            "н" => "n",
            "о" => "o",
            "п" => "p",
            "р" => "r",
            "с" => "s",
            "т" => "t",
            "у" => "u",
            "ф" => "f",
            "х" => "h",
            "ц" => "ts",
            "ч" => "ch",
            "ш" => "sh",
            "щ" => "sch",
            "ъ" => "y",
            "ы" => "i",
            "ь" => "j",
            "э" => "e",
            "ю" => "yu",
            "я" => "ya",
            " " => "_",
            "." => "",
            "/" => "_",
            "," => "_",
            "-" => "_",
            "(" => "",
            ")" => "",
            "[" => "",
            "]" => "",
            "=" => "_",
            "+" => "_",
            "*" => "",
            "?" => "",
            "\"" => "",
            "'" => "",
            "&" => "",
            "%" => "",
            "#" => "",
            "@" => "",
            "!" => "",
            ";" => "",
            "№" => "",
            "^" => "",
            ":" => "",
            "~" => "",
            "\\" => ""
        ];
        return strtr($str, $tr);
    }
}
