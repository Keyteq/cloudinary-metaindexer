<?php

namespace Keyteq\Bundle\CloudinaryMetaIndexer\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Doctrine\Common\Proxy\AbstractProxyFactory;
use Doctrine\ODM\MongoDB\Configuration as ODMConfiguration;
use Symfony\Bridge\Doctrine\DataFixtures\ContainerAwareLoader;

class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('keyteq_cloudinary_meta_indexer');


        $rootNode
            ->children()
            ->scalarNode('cloudinary_api_key')->isRequired()->end()
            ->scalarNode('cloudinary_api_secret')->isRequired()->end()
            ->scalarNode('cloudinary_cloud_name')->isRequired()->end()
            ->end()
        ;


        // Here you should define the parameters that are allowed to
        // configure your bundle. See the documentation linked above for
        // more information on that topic.

        return $treeBuilder;
    }
}
