<?php

namespace Keyteq\Bundle\CloudinaryMetaIndexer\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

class CompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {        // For 6.X+ we use the new ContentView.
        if (class_exists('\eZ\Publish\Core\MVC\Symfony\View\BaseView')) {
            $container->setParameter('keyteq_cloudinary_meta_indexer.controller.full', 'keyteq.cloudinary_meta_indexer.controller.full_view:viewCloudinaryPage');
        } else {
            // Fallback to use old controller.
            $container->setParameter('keyteq_cloudinary_meta_indexer.controller.full', 'keyteq.cloudinary_meta_indexer.controller.full_view:viewCloudinaryPageLocation');
        }
    }
}
