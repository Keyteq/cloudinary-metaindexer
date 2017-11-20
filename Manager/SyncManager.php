<?php
/**
 * Created by PhpStorm.
 * User: petterkjelkenes
 * Date: 14.11.2017
 * Time: 13.26
 */
namespace Keyteq\Bundle\CloudinaryMetaIndexer\Manager;

use Keyteq\Bundle\CloudinaryMetaIndexer\Adapter\AdapterInterface;
use Keyteq\Bundle\CloudinaryMetaIndexer\Document\CloudinaryResource;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Console\Output\OutputInterface;

class SyncManager
{
    /**
     * @var StorageManager
     */
    protected $storageManager;

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

    public function __construct(StorageManager $storageManager, AdapterInterface $adapter, array $config, LoggerInterface $logger = null)
    {
        $this->storageManager = $storageManager;
        $this->config = $config;
        $this->logger = $logger instanceof LoggerInterface ? $logger : new NullLogger();
        $this->adapter = $adapter;
    }

    public function sync(OutputInterface $output)
    {
        try {
            $dm = $this->storageManager->getManager();
            $existingPubIds = array();
            $addedPubIds = array();
            $all = $dm->getRepository(CloudinaryResource::class)->findAll();
            foreach ($all as $resource) {
                $existingPubIds[] = $resource->getPublicId();
            }

            // Make sure to pass array &$addedPubIds as reference ( to avoid deletion of actual existing items ).
            $this->adapter->getResources(function ($items) use ($dm, $output, &$addedPubIds) {
                $output->writeln("Processing batch of cloudinary items...");
                foreach ($items as $item) {
                    $resource = $dm->find(CloudinaryResource::class, $item['public_id']);

                    if (!$resource) {
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

                    $dm->persist($resource);

                    $addedPubIds[] = $item['public_id'];
                }
            });

            // Removing resources from mongodb that is no longer in cloudinary.
            foreach ($existingPubIds as $existingPubId) {
                if (!in_array($existingPubId, $addedPubIds)) {
                    $document = $dm->find(CloudinaryResource::class, $existingPubId);
                    $dm->remove($document);
                    $output->writeln("Removing pub-id {$existingPubId}.");
                }
            }
            $dm->flush();
        } catch (\Exception $e) {
            $this->logException($e);
            throw $e;
        }
    }

    /**
     * If logger is defined and exists, log given exception's message.
     *
     * @param \Exception $e
     */
    protected function logException(\Exception $e)
    {
        $this->logger->notice(get_class($e) . ': ' . $e->getMessage(), $e->getTrace());
    }

}