<?php
/**
 * Created by PhpStorm.
 * User: petterkjelkenes
 * Date: 14.11.2017
 * Time: 13.26
 */

namespace Keyteq\Bundle\CloudinaryMetaIndexer\Manager;

class SyncManager
{
    protected $storageManager;

    protected $config;

    public function __construct(StorageManager $storageManager, $config)
    {
        $this->storageManager = $storageManager;
        $this->config = $config;
    }


    public function sync () {

        \Cloudinary::config(array(
            "cloud_name" => $this->config['cloudinary_cloud_name'],
            "api_key" => $this->config['cloudinary_api_key'],
            "api_secret" => $this->config['cloudinary_api_secret']
        ));

        $api = new \Cloudinary\Api();

        print_r($api->resources());

        // Add to mongodb.
        $dm = $this->storageManager->getManager();

    }


}