<?php
/**
 * Created by PhpStorm.
 * User: petterkjelkenes
 * Date: 15.11.2017
 * Time: 10.49
 */

namespace Keyteq\Bundle\CloudinaryMetaIndexer\Controller;


use eZ\Bundle\EzPublishCoreBundle\Controller;
use Keyteq\Bundle\CloudinaryMetaIndexer\Manager\StorageManager;

class CloudinaryResourceController extends Controller
{
    /**
     * @var StorageManager
     */
    protected $storageManager;

    /**
     * DefaultController constructor.
     * @param StorageManager $storageManager
     */
    public function __construct(StorageManager $storageManager)
    {
        $this->storageManager = $storageManager;
    }

    public function viewResourcesAction()
    {

    }

}