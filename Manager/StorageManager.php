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
use Keyteq\Bundle\CloudinaryMetaIndexer\Document\CloudinaryResource;
use Symfony\Component\HttpKernel\Config\FileLocator;

class StorageManager
{
    /**
     * @var DocumentManager
     */
    protected $dm;

    /**
     * StorageManager constructor.
     * @param FileLocator $fileLocator
     * @param array $bundleConfiguration
     */
    public function __construct(FileLocator $fileLocator, array $bundleConfiguration)
    {
        $config = new Configuration();
        $config->setProxyDir($bundleConfiguration['mongodb']['proxies_path']);
        $config->setProxyNamespace('CloudinaryMetaIndexerProxies');
        $config->setAutoGenerateProxyClasses($bundleConfiguration['mongodb']['autogenerate_proxies']);
        $config->setHydratorDir($bundleConfiguration['mongodb']['hydrators_path']);
        $config->setHydratorNamespace('CloudinaryMetaIndexerHydrators');
        $config->setAutoGenerateHydratorClasses($bundleConfiguration['mongodb']['autogenerate_hydrators']);
        $config->setDefaultDB($bundleConfiguration['mongodb']['database']);

        $resourcePath = $fileLocator->locate('@KeyteqCloudinaryMetaIndexerBundle/Resources/config/doctrine');
        $driver = new YamlDriver(array($resourcePath), '.mongodb.yml');
        $config->setMetadataDriverImpl($driver);

        $connection = new Connection($bundleConfiguration['mongodb']['server']);

        $this->dm = DocumentManager::create($connection, $config);

        // Configure cloudinary, certain preview functions for e.g. thumbnail generation needs this to be configured.
        \Cloudinary::config(array(
            "cloud_name" => $bundleConfiguration['cloudinary_cloud_name'],
            "api_key" => $bundleConfiguration['cloudinary_api_key'],
            "api_secret" => $bundleConfiguration['cloudinary_api_secret']
        ));
    }

    /**
     * @return DocumentManager
     */
    public function getManager()
    {
        return $this->dm;
    }

    /**
     * Returns CloudinaryResource objects based on the filtering given as arguments.
     *
     * @param array $tags To filter on array of tags
     * @param null|string $search If you want to search in the field values
     * @return mixed
     */
    public function getResources($tags = [], $search = null)
    {
        $query = $this->getManager()->createQueryBuilder(CloudinaryResource::class);
        if ($tags) {
            $query->field('tags')->in($tags);
        }
        if ($search) {
            $query->addAnd($query->expr()->field('publicId')->equals(new \MongoRegex('/.*'.$search.'.*/i')));
        }
        $query->sort('createdAt', 'desc');
        $resources = $query->getQuery()->execute();
        return $resources;
    }
}