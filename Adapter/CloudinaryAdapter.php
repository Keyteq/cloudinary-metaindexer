<?php
/**
 * Created by PhpStorm.
 * User: petterkjelkenes
 * Date: 16.11.2017
 * Time: 09.41
 */

namespace Keyteq\Bundle\CloudinaryMetaIndexer\Adapter;

use Cloudinary;
use Cloudinary\Api;

class CloudinaryAdapter implements AdapterInterface
{
    public function __construct(array $bundleConfiguration)
    {
        // Configure cloudinary, certain preview functions for e.g. thumbnail generation needs this to be configured.
        Cloudinary::config(array(
            "cloud_name" => $bundleConfiguration['cloudinary_cloud_name'],
            "api_key" => $bundleConfiguration['cloudinary_api_key'],
            "api_secret" => $bundleConfiguration['cloudinary_api_secret']
        ));
    }

    public function getResources()
    {
        $api = new Api();
        $result = $api->resources(array(
            'resource_type' => 'image',
            'tags' => true,
            'context' => true
        ));
        return $result;
    }
}