<?php

namespace Keyteq\Bundle\CloudinaryMetaIndexer;

use Keyteq\Bundle\CloudinaryMetaIndexer\DependencyInjection\Compiler\CompilerPass;
use Keyteq\Bundle\CloudinaryMetaIndexer\DependencyInjection\KeyteqCloudinaryMetaIndexerExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class KeyteqCloudinaryMetaIndexerBundle extends Bundle
{

    public function build(ContainerBuilder $container)
    {
        parent::build($container);
        $container->addCompilerPass(new CompilerPass());
    }
    public function getContainerExtension()
    {
        return new KeyteqCloudinaryMetaIndexerExtension();
    }
}
