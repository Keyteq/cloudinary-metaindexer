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
use Keyteq\Bundle\CloudinaryMetaIndexer\Adapter\Exception\ResourceLoopException;

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

    /**
     * Gets resources and calls callback with the items available.
     *
     * @param callable $itemsCallback Called function per page of items returned from the rest api.
     * @throws ResourceLoopException if rate limited by api or unable to parse api results.
     * @return void
     */
    public function getResources(callable $itemsCallback)
    {
        $api = new Api();
        $nextCursor = null;

        $types = array('raw', 'image', 'video');

        foreach ($types as $type) {
            do {
                $params = array(
                    'resource_type' => $type,
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

                if ($nextCursor && $result->rate_limit_remaining == 0) {
                    throw new ResourceLoopException("Rate limit exceeded for API calls to cloudinary. Max calls: {$result->rate_limit_allowed}. Limit resets at " . date('d.m-Y H:s', $result->rate_limit_reset_at));
                }
            } while($nextCursor !== null);
        }

    }
}