<?php

namespace Keyteq\Bundle\CloudinaryMetaIndexer\Manager;

use Doctrine\MongoDB\Connection;
use Doctrine\ODM\MongoDB\Configuration;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\Driver\YamlDriver;
use Doctrine\ORM\EntityManagerInterface;
use Keyteq\Bundle\CloudinaryMetaIndexer\Adapter\AdapterInterface;
use Keyteq\Bundle\CloudinaryMetaIndexer\Entity\CloudinaryResource;
use Pagerfanta\Adapter\DoctrineODMMongoDBAdapter;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use Pagerfanta\Pagerfanta;
use Symfony\Component\HttpKernel\Config\FileLocator;

final class StorageManager
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * Returns CloudinaryResource objects based on the filtering given as arguments.
     */
    public function getResources(array $tags = [], array $searchTags = [], $search = null, $publidIdPrefix = null): Pagerfanta
    {
        $query = $this->getQuery($tags, $searchTags, $search, $publidIdPrefix);

        $adapter = new QueryAdapter($query);
        $pager = new Pagerfanta($adapter);

        return $pager;
    }

    public function getTagCloud
    (
        array $tagsRequired = [],
        array $queryFilterableTags = [],
        $search = null,
        $publidIdPrefix = null,
        $sortField = 'count',
        $sortMethod = SORT_DESC
    ) {
        // Make sure there are no empty elements.
        $queryFilterableTags = array_filter($queryFilterableTags, function($value) { return trim($value) !== ''; });
        $query = $this->getQuery($tagsRequired, $queryFilterableTags, $search, $publidIdPrefix);
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

    private function getQuery(array $tags = [], array $searchTags = [], $search = null, $publidIdPrefix = null)
    {
        // Make sure there are no empty elements.
        $tags = array_filter($tags, function($value) { return $value !== ''; });
        $searchTags = array_filter($searchTags, function($value) { return $value !== ''; });

        foreach ($tags as $k => $v) {
            $tags[$k] = trim($v);
        }
        foreach ($searchTags as $k => $v) {
            $searchTags[$k] = trim($v);
        }

        /** @var \Doctrine\ORM\EntityRepository $repository */
        $repository = $this->entityManager->getRepository(CloudinaryResource::class);
        $query = $repository->createQueryBuilder('cr');

        $allTags = array_merge($tags, $searchTags);

        if (!empty($allTags)) {
            $allTags = implode($allTags, ',');
            $query->andWhere(
                $query->expr()->like('cr.tags', '?1')
            );
            $query->setParameter('1', '%'.$allTags.'%');
        }

        if ($search) {
            $query->andWhere(
                $query->expr()->addOr(
                    $query->expr()->like('cr.publicId', $search),
                    $query->expr()->in('cr.tags', $search)
                )
            );
        }

        if ($publidIdPrefix) {
            $query->andWhere(
                $query->expr()->like('cr.publicId', $publidIdPrefix.'%')
            );
        }

        $query->orderBy('cr.createdAt', 'desc');

        return $query;
    }
}
