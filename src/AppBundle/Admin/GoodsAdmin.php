<?php

namespace AppBundle\Admin;

use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Show\ShowMapper;

class GoodsAdmin extends AbstractAdmin
{
    /**
     * @param DatagridMapper $datagridMapper
     */
    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper
            ->add('id')
            ->add('alias')
            ->add('externalId')
            ->add('vendorCode')
            ->add('category')
            ->add('model')
            ->add('name')
            ->add('currency')
            ->add('price')
            ->add('oldPrice')
            ->add('url')
            ->add('isDelete')
        ;
    }

    /**
     * @param ListMapper $listMapper
     */
    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->add('id')
            ->add('alias')
            ->add('externalId')
            ->add('groupId')
            ->add('category')
            ->add('name')
            ->add('price')
            ->add('oldPrice')
            ->add('isDelete')
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
            ->add('externalId')
            ->add('alias')
            ->add('vendorCode')
            ->add('category')
            ->add('model')
            ->add('name')
            ->add('description')
            ->add('currency')
            ->add('price')
            ->add('oldPrice')
            ->add('url')
            ->add('isDelete')
        ;
    }

    /**
     * @param ShowMapper $showMapper
     */
    protected function configureShowFields(ShowMapper $showMapper)
    {
        $showMapper
            ->add('id')
            ->add('externalId')
            ->add('vendorCode')
            ->add('category')
            ->add('model')
            ->add('name')
            ->add('description')
            ->add('currency')
            ->add('price')
            ->add('oldPrice')
            ->add('url')
            ->add('isDelete')
        ;
    }
}
