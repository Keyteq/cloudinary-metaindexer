<?php

namespace Keyteq\Bundle\CloudinaryMetaIndexer\DependencyInjection;

use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Yaml\Yaml;

class KeyteqCloudinaryMetaIndexerExtension extends Extension implements PrependExtensionInterface
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $container->setParameter('cloudinary_meta_indexer.config', $config);

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('parameters.yml');
        $loader->load('services.yml');
    }
    public function prepend(ContainerBuilder $container)
    {
        $configFile = __DIR__ . '/../Resources/config/ezplatform.yml';
        $config = Yaml::parse(file_get_contents($configFile));

        // For 6.X+ we use the new ContentView.
        if (class_exists('\eZ\Publish\Core\MVC\Symfony\View\BaseView')) {
            $config['ezpublish']['system']['default']['content_view']['full']['cloudinary_page']['controller'] = 'keyteq.cloudinary_meta_indexer.controller.full_view:viewCloudinaryPage';
        } else {
            $config['ezpublish']['system']['default']['content_view']['full']['cloudinary_page']['controller'] = 'keyteq.cloudinary_meta_indexer.controller.full_view:viewCloudinaryPageLocation';
        }

        $container->prependExtensionConfig('ezpublish', $config['ezpublish']);
        $container->addResource(new FileResource($configFile));
    }
}
