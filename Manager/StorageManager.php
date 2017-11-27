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
use Keyteq\Bundle\CloudinaryMetaIndexer\Adapter\AdapterInterface;
use Keyteq\Bundle\CloudinaryMetaIndexer\Document\CloudinaryResource;
use Pagerfanta\Adapter\DoctrineODMMongoDBAdapter;
use Pagerfanta\Pagerfanta;
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
     * @param AdapterInterface $adapter
     * @param array $bundleConfiguration
     */
    public function __construct(FileLocator $fileLocator, AdapterInterface $adapter, array $bundleConfiguration)
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
     * @return Pagerfanta
     */
    public function getResources($tags = [], $search = null, $publidIdPrefix = null)
    {
        $query = $this->getQuery($tags, $search, $publidIdPrefix);
        $adapter = new DoctrineODMMongoDBAdapter($query);
        $pager = new Pagerfanta($adapter);
        return $pager;
    }

    public function getTagCloud (
        $queryFilterableTags = [],
        $tagsRequired = [],
        $search = null,
        $publidIdPrefix = null,
        $sortField = 'count',
        $sortMethod = SORT_DESC
    ) {
        // Make sure there are no empty elements.
        $queryFilterableTags = array_filter($queryFilterableTags, function($value) { return trim($value) !== ''; });
        $searchTags = array_merge($tagsRequired,$queryFilterableTags);
        $query = $this->getQuery($searchTags, $search, $publidIdPrefix);
        $resources = $query->getQuery()->execute();
        $cloud = [];

        /** @var CloudinaryResource $resource */
        foreach ($resources as $resource) {
            $resourceTags = $resource->getTags();
            if ($resourceTags) {
                foreach ($resourceTags as $tag) {
                    // Exclude internal non-filterable tags.
                    if (!in_array($tag, $tagsRequired)) {
                        if (!isset($cloud[$tag])) {
                            $cloud[$tag] = [
                                'name' => $tag,
                                'count' => 1,
                                'active' => in_array($tag, $queryFilterableTags)
                            ];
                        } else {
                            $cloud[$tag]['count']++;
                        }
                    }
                }
            }
        }

        $count = array();
        foreach ($cloud as $key => $row)
        {
            $count[$key] = $row[$sortField];
        }
        array_multisort($count, $sortMethod, $cloud);

        return $cloud;
    }

    private function getQuery ($tags = [], $search = null, $publidIdPrefix = null) {
        // Make sure there are no empty elements.
        $tags = array_filter($tags, function($value) { return $value !== ''; });

        $query = $this->getManager()->createQueryBuilder(CloudinaryResource::class);
        if ($tags) {
            $query->field('tags')->all($tags);
        }
        if ($search) {
            $searchRegex = new \MongoRegex('/.*'.preg_quote($search, '/').'.*/i');
            $query->addAnd(
                $query->expr()->addOr(
                    $query->expr()->field('publicId')->equals($searchRegex),
                    $query->expr()->field('tags')->in(array($searchRegex))
                )
            );
        }
        if ($publidIdPrefix) {
            $startsWithRegex = new \MongoRegex('/^'.preg_quote($publidIdPrefix, '/').'.*/');
            $query->addAnd(
                $query->expr()->field('publicId')->equals($startsWithRegex)
            );
        }
        $query->sort('createdAt', 'desc');
        return $query;
    }
}