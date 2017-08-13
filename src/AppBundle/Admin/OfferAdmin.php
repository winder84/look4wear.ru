<?php

namespace AppBundle\Admin;

use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Show\ShowMapper;

class OfferAdmin extends AbstractAdmin
{
    /**
     * @param DatagridMapper $datagridMapper
     */
    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper
            ->add('id')
            ->add('name')
            ->add('description')
            ->add('xmlParseUrl')
            ->add('deliveryUrl')
            ->add('paymentUrl')
            ->add('url')
            ->add('alias')
            ->add('version')
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
            ->add('name')
            ->add('description')
            ->add('xmlParseUrl')
            ->add('deliveryUrl')
            ->add('paymentUrl')
            ->add('url')
            ->add('alias')
            ->add('version')
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
            ->add('isDelete')
            ->add('name')
            ->add('description')
            ->add('xmlParseUrl')
            ->add('deliveryUrl')
            ->add('paymentUrl')
            ->add('url')
            ->add('alias')
            ->add('version')
        ;
    }

    /**
     * @param ShowMapper $showMapper
     */
    protected function configureShowFields(ShowMapper $showMapper)
    {
        $showMapper
            ->add('id')
            ->add('name')
            ->add('description')
            ->add('xmlParseUrl')
            ->add('deliveryUrl')
            ->add('paymentUrl')
            ->add('url')
            ->add('alias')
            ->add('version')
        ;
    }
}
