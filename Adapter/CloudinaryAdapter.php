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
    const MAX_PAGE_RESULTS = 500;


    public function __construct(array $bundleConfiguration)
    {
        // Configure cloudinary, certain preview functions for e.g. thumbnail generation needs this to be configured.
        Cloudinary::config(array(
            "cloud_name" => $bundleConfiguration['cloudinary_cloud_name'],
            "api_key" => $bundleConfiguration['cloudinary_api_key'],
            "api_secret" => $bundleConfiguration['cloudinary_api_secret']
        ));
    }

    public function getResources(callable $itemsCallback)
    {
        $api = new Api();
        $nextCursor = null;
        do {
            $params = array(
                'resource_type' => 'image',
                'tags' => true,
                'context' => true,
                'max_results' => self::MAX_PAGE_RESULTS
            );
            if ($nextCursor !== null) {
                $params['next_cursor'] = $nextCursor;
            }
            $result = $api->resources($params);
            call_user_func_array($itemsCallback, [$result['resources']]);
            $nextCursor = isset($result['next_cursor']) && $result['next_cursor'] ? $result['next_cursor'] : null;
        } while($nextCursor !== null);
    }
}