<?php
namespace AppBundle\Block\Service;

use Sonata\BlockBundle\Block\BlockContextInterface;
use Sonata\BlockBundle\Block\Service\AbstractBlockService;
use Sonata\BlockBundle\Block\Service\BlockServiceInterface;
use Sonata\BlockBundle\Model\BlockInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class L4wInfo extends AbstractBlockService implements BlockServiceInterface
{

    public function configureOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults([
            'title' => 'L4wInfo',
            'template' => 'AppBundle:look4wear:l4w.info.block.html.twig',
        ]);
    }

    public function getName()
    {
        return "L4wInfo";
    }

    public function getCacheKeys(BlockInterface $block)
    {
        return [];
    }

    public function getJavascripts($media)
    {
        return [];
    }

    public function getStylesheets($media)
    {
        return [];
    }

    public function execute(BlockContextInterface $blockContext, Response $response = null)
    {

        return $this->renderResponse('AppBundle:look4wear:l4w.info.block.html.twig', [
            'block' => $blockContext->getBlock(),
            'settings' => $blockContext->getSettings(),
            'info' => [
                'title' => 'Заголовок',
                'content' => 'Контент',
            ],
        ], $response);
    }
}

