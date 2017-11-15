<?php
/**
 * Created by PhpStorm.
 * User: petterkjelkenes
 * Date: 15.11.2017
 * Time: 11.01
 */

namespace Keyteq\Bundle\CloudinaryMetaIndexer\Controller;

use eZ\Bundle\EzPublishCoreBundle\Controller;
use eZ\Publish\Core\MVC\Symfony\View\BaseView;
use eZ\Publish\Core\MVC\Symfony\View\ContentView;
use Keyteq\Bundle\CloudinaryMetaIndexer\Document\CloudinaryResource;
use Keyteq\Bundle\CloudinaryMetaIndexer\Manager\StorageManager;
use Symfony\Component\HttpFoundation\Request;

class FullViewController extends Controller
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

    /**
     * Controller action for viewing a cloudinary_page location.
     * Supports Netgen site api out of the box.
     *
     * @param Request $request
     * @param BaseView $view
     * @return ContentView|\Netgen\Bundle\EzPlatformSiteApiBundle\View\ContentView
     */
    public function viewCloudinaryPage ( Request $request, BaseView $view ) {
        $search = trim($request->get('s'));
        $content = $view->getContent();
        $tags = $content->getFieldValue('tags')->text;

        if ($tags) {
            $tags = explode(',', $tags);
        }

        $resources = $this->storageManager->getResources($tags, $search);

        $view->addParameters(array(
            'resources' => $resources
        ));

        if ($view instanceof \Netgen\Bundle\EzPlatformSiteApiBundle\View\ContentView) {
            $response = $this->get('ng_content')->viewAction($view);
        } else {
            $response = $this->get('ez_content')->viewAction($view);
        }
        return $response;
    }

}