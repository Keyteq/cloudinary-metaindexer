<?php
/**
 * Created by PhpStorm.
 * User: petterkjelkenes
 * Date: 14.11.2017
 * Time: 13.26
 */

namespace Keyteq\Bundle\CloudinaryMetaIndexer\Manager;


use Doctrine\MongoDB\Connection;
use Doctrine\ODM\MongoDB\Configuration;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\Driver\YamlDriver;
use Symfony\Component\HttpKernel\Config\FileLocator;

class StorageManager
{

    protected $fileLocater;

    protected $proxiesCachePath;

    protected $hydratorsCachePath;

    protected $dm;

    public function __construct(FileLocator $fileLocator, $proxiesCachePath, $hydratorsCachePath, $server)
    {
        $this->fileLocator = $fileLocator;
        $this->proxiesCachePath = $proxiesCachePath;
        $this->hydratorsCachePath = $hydratorsCachePath;

        $config = new Configuration();
        $config->setProxyDir($this->proxiesCachePath);
        $config->setProxyNamespace('CloudinaryMetaIndexerProxies');
        $config->setHydratorDir($this->hydratorsCachePath);
        $config->setHydratorNamespace('CloudinaryMetaIndexerHydrators');

        $resourcePath = $this->fileLocator->locate('@KeyteqCloudinaryMetaIndexerBundle/Resources/config/doctrine');
        $driver = new YamlDriver(array($resourcePath), '.mongodb.yml');
        $config->setMetadataDriverImpl($driver);

        $connection = new Connection($server);

        $this->dm =  DocumentManager::create($connection, $config);
    }

    public  function getManager () {
        return $this->dm;
    }


}