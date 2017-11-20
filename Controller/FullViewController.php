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
    public function viewCloudinaryPage(Request $request, BaseView $view) {
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


    /**
     * Use for eZ Publish 5.X only. If on eZ Platform, use viewCloudinaryPage instead.
     *
     * @param Request $request
     * @param $locationId
     * @param $viewType
     * @param bool $layout
     * @param array $params
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function viewCloudinaryPageLocation( Request $request, $locationId, $viewType, $layout = false, array $params = array() )
    {
        $location = $this->getRepository()->getLocationService()->loadLocation( $locationId );
        $content = $this->getRepository()->getContentService()->loadContent( $location->contentId );
        $search = trim($request->get('s'));
        $tags = $content->getFieldValue('tags')->text;

        if ($tags) {
            $tags = explode(',', $tags);
        }

        $resources = $this->storageManager->getResources($tags, $search);

        $params = $params + array(
                'resources' => $resources
            );

        return $this->container->get( 'ez_content' )->viewLocation( $locationId, $viewType, $layout, $params );
    }

}