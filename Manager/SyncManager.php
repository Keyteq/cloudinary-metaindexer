<?php
/**
 * Created by PhpStorm.
 * User: petterkjelkenes
 * Date: 14.11.2017
 * Time: 13.26
 */
namespace Keyteq\Bundle\CloudinaryMetaIndexer\Manager;

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

    public function __construct(StorageManager $storageManager, array $config, LoggerInterface $logger = null)
    {
        $this->storageManager = $storageManager;
        $this->config = $config;
        $this->logger = $logger instanceof LoggerInterface ? $logger : new NullLogger();
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
            $api = new \Cloudinary\Api();
            $result = $api->resources(array(
                'resource_type' => 'image',
                'tags' => true,
                'context' => true
            ));
            // Add / update items based on the api results
            foreach ($result as $items) {
                foreach ($items as $item) {
                    $resource = $dm->find(CloudinaryResource::class, $item['public_id']);

                    if (!$resource) {
                        $resource = new CloudinaryResource();
                        $resource->setPublicId($item['public_id']);
                        $output->writeln("Adding pub-id {$item['public_id']}");
                    } else {
                        $output->writeln("Updating pub-id {$item['public_id']}");
                    }
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
                    $dm->persist($resource);

                    $addedPubIds[] = $item['public_id'];
                }
            }
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