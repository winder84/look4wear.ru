<?php

namespace AppBundle\Admin;

use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Show\ShowMapper;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

class CategoryAdmin extends AbstractAdmin
{
    /**
     * @param DatagridMapper $datagridMapper
     */
    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper
            ->add('id')
            ->add('name')
            ->add('title')
            ->add('seoTitle')
            ->add('alias')
            ->add('searchString')
            ->add('excludeWords')
            ->add('keywords')
        ;
    }

    /**
     * @param ListMapper $listMapper
     */
    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->add('id')
            ->add('name')
            ->add('parentCategory')
            ->add('title')
            ->add('alias')
            ->add('searchString')
            ->add('_action', null, array(
                'actions' => array(
                    'show' => array(),
                    'edit' => array(),
                    'delete' => array(),
                ),
            ))
        ;
    }

    /**
     * @param FormMapper $formMapper
     */
    protected function configureFormFields(FormMapper $formMapper)
    {
        $formMapper
            ->add('name')
            ->add('title')
            ->add('seoTitle')
            ->add('alias')
            ->add('searchString')
            ->add('excludeWords')
            ->add('keywords')
            ->add('parentCategory', EntityType::class, array(
                'class' => 'AppBundle:Category',
                'choice_label' => 'name',
                'label' => 'Родительская категория',
                'empty_value' => true,
                'placeholder' => 'Нет',
                'required' => false,
            ))
        ;
    }

    /**
     * @param ShowMapper $showMapper
     */
    protected function configureShowFields(ShowMapper $showMapper)
    {
        $showMapper
            ->add('name')
            ->add('title')
            ->add('seoTitle')
            ->add('alias')
            ->add('searchString')
            ->add('excludeWords')
            ->add('keywords')
        ;
    }
}
