<?php

namespace Keyteq\Bundle\CloudinaryMetaIndexer\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\Config\FileLocator;

class KeyteqCloudinaryMetaIndexerExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $container->setParameter('cloudinary_meta_indexer.config', $config);

        // For 6.X+ we use the new ContentView.
        if (class_exists('\eZ\Publish\Core\MVC\Symfony\View\BaseView')) {
            $container->setParameter('keyteq_cloudinary_meta_indexer.full.controller', 'keyteq.cloudinary_meta_indexer.controller.full_view:viewCloudinaryPage');
        } else {
            // Fallback to use old controller.
            $container->setParameter('keyteq_cloudinary_meta_indexer.full.controller', 'keyteq.cloudinary_meta_indexer.controller.full_view:viewCloudinaryPageLocation');
        }

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yml');
    }
}
