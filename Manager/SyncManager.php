<?php

namespace Keyteq\Bundle\CloudinaryMetaIndexer\Manager;

use Doctrine\ORM\EntityManagerInterface;
use eZ\Publish\API\Repository\Values\Content\LocationQuery;
use eZ\Publish\Core\MVC\Symfony\Cache\Http\InstantCachePurger;
use EzSystems\PlatformHttpCacheBundle\PurgeClient\PurgeClientInterface;
use Keyteq\Bundle\CloudinaryMetaIndexer\Adapter\AdapterInterface;
use Keyteq\Bundle\CloudinaryMetaIndexer\Entity\CloudinaryResource;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Console\Output\OutputInterface;
use eZ\Publish\API\Repository\Repository;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion;

class SyncManager
{
    private EntityManagerInterface $entityManager;

    /**
     * @var array
     */
    protected $config;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @var AdapterInterface
     */
    protected $adapter;

    /**
     * @var Repository
     */
    protected $repository;

    /**
     * @var string
     */
    protected $contentTypeIdentifier;

    /**
     * @var \EzSystems\PlatformHttpCacheBundle\PurgeClient\PurgeClientInterface
     */
    protected $purgeClient;

    public function __construct(
        EntityManagerInterface $entityManager,
        AdapterInterface $adapter,
        array $config,
        Repository $repository,
        $contentTypeIdentifier,
        PurgeClientInterface $purgeClient,
        LoggerInterface $logger = null)
    {
        $this->entityManager = $entityManager;


        $this->contentTypeIdentifier = $contentTypeIdentifier;
        $this->repository = $repository;
        $this->config = $config;
        $this->logger = $logger instanceof LoggerInterface ? $logger : new NullLogger();
        $this->adapter = $adapter;
        $this->purgeClient = $purgeClient;
    }

    /**
     * Runs calls to cloudinary API and stores the data in a mongodb database.
     *
     * @param OutputInterface $output
     * @throws \Exception
     */
    public function sync(OutputInterface $output)
    {
        try {
            $existingPubIds = array();
            $addedPubIds = array();

            $repository = $this->entityManager->getRepository(CloudinaryResource::class);
            $all = $repository->findAll();

            foreach ($all as $resource) {
                $existingPubIds[] = $resource->getPublicId();
            }

            // Make sure to pass array &$addedPubIds as reference (to avoid deletion of actual existing items).
            $this->adapter->getResources(function ($items) use ($repository, $output, &$addedPubIds) {
                $output->writeln("Processing batch of cloudinary items...");
                foreach ($items as $item) {
                    $resource = $repository->find($item['public_id']);

                    if (!$resource instanceof CloudinaryResource) {
                        $resource = new CloudinaryResource();
                        $resource->setPublicId($item['public_id']);
                        $output->writeln("Adding pub-id {$item['public_id']}");
                    } else {
                        $output->writeln("Updating pub-id {$item['public_id']}");
                    }

                    switch($item['resource_type']) {
                        case 'raw':
                            $resource->setContext(isset($item['context']) ? $item['context'] : null);
                            $resource->setVersion($item['version']);
                            $resource->setResourceType($item['resource_type']);
                            $resource->setType($item['type']);
                            $resource->setCreatedAt($item['created_at']);
                            $resource->setBytes($item['bytes']);
                            $resource->setUrl($item['url']);
                            $resource->setSecureUrl($item['secure_url']);
                            $resource->setTags($item['tags']);
                            break;
                        case 'image':
                        case 'video':
                            $resource->setBytes($item['bytes']);
                            $resource->setCreatedAt($item['created_at']);
                            $resource->setFormat($item['format']);
                            $resource->setHeight($item['height']);
                            $resource->setResourceType($item['resource_type']);
                            $resource->setSecureUrl($item['secure_url']);
                            $resource->setType($item['type']);
                            $resource->setUrl($item['url']);
                            $resource->setVersion($item['version']);
                            $resource->setWidth($item['width']);
                            $resource->setTags($item['tags']);
                            $resource->setContext(isset($item['context']) ? $item['context'] : null);
                            break;
                    }

                    $this->entityManager->persist($resource);

                    $addedPubIds[] = $item['public_id'];
                }
            });

            // Removing resources from mongodb that is no longer in cloudinary.
            foreach ($existingPubIds as $existingPubId) {
                if (!in_array($existingPubId, $addedPubIds, true)) {
                    $document = $repository->find($existingPubId);
                    $this->entityManager->remove($document);
                    $output->writeln("Removing pub-id {$existingPubId}.");
                }
            }
            $this->entityManager->flush();
        } catch (\Exception $e) {
            $this->logException($e);
            throw $e;
        }

        $this->purgeCloudinaryPageLocationCache();
    }

    /**
     * Purges cache for all cloudinary pages.
     */
    protected function purgeCloudinaryPageLocationCache ()
    {
        // Find all cloudinary_page objects and purge cache of those.
        $searchService = $this->repository->getSearchService();
        $query = new LocationQuery();
        $query->filter = new Criterion\ContentTypeIdentifier($this->contentTypeIdentifier);
        $searchResult = $searchService->findLocations( $query );
        $locationIdsToPurge = array();
        foreach ($searchResult->searchHits as $hit) {
            $location = $hit->valueObject;
            $locationIdsToPurge[] = $location->id;
        }
        if ($locationIdsToPurge) {
            $this->purgeClient->purge($locationIdsToPurge);
        }
    }

    /**
     * If logger is defined and exists, log given exception's message.
     *
     * @param \Exception $e
     */
    protected function logException(\Exception $e)
    {
        $this->logger->error(get_class($e) . ': ' . $e->getMessage(), $e->getTrace());
    }
}
