<?php

namespace Keyteq\Bundle\CloudinaryMetaIndexer\Controller;

use eZ\Bundle\EzPublishCoreBundle\Controller;
use eZ\Publish\Core\MVC\Symfony\View\BaseView;
use eZ\Publish\Core\MVC\Symfony\View\ContentView;
use Keyteq\Bundle\CloudinaryMetaIndexer\Manager\StorageManager;
use Symfony\Component\HttpFoundation\Request;

final class FullViewController extends Controller
{
    protected StorageManager $storageManager;

    public function __construct(StorageManager $storageManager)
    {
        $this->storageManager = $storageManager;
    }

    /**
     * For eZ Platform only. Controller action for viewing a cloudinary_page location.
     * Supports Netgen site api out of the box.
     *
     * @see \Keyteq\Bundle\CloudinaryMetaIndexer\Controller\FullViewController::viewCloudinaryPageLocation If you are running eZ Publish 5.X.
     * @param Request $request
     * @param BaseView $view
     * @return ContentView|\Netgen\Bundle\EzPlatformSiteApiBundle\View\ContentView
     */
    public function viewCloudinaryPage(Request $request, BaseView $view)
    {
        $search = trim($request->get('s'));
        $content = $view->getContent();
        $result = $this->getPager($request, $content, $search);

        $view->addParameters(array(
            'activeSearchTags' => $result['activeSearchTags'],
            'resources' => $result['pager'],
            'tagCloud' => $result['tagCloud'],
            'searchText' => $search
        ));

        if ($view instanceof \Netgen\Bundle\EzPlatformSiteApiBundle\View\ContentView) {
            $response = $this->get('ng_content')->viewAction($view);
        } else {
            $response = $this->get('ez_content')->viewAction($view);
        }
        $response->setCacheEnabled(true);
        return $response;
    }


    /**
     * Use for eZ Publish 5.X only.
     *
     *
     * @see \Keyteq\Bundle\CloudinaryMetaIndexer\Controller\FullViewController::viewCloudinaryPage If you are running eZ Platform.
     * @param Request $request
     * @param $locationId
     * @param $viewType
     * @param bool $layout
     * @param array $params
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function viewCloudinaryPageLocation(Request $request, $locationId, $viewType, $layout = false, array $params = array())
    {
        $location = $this->getRepository()->getLocationService()->loadLocation( $locationId );
        $content = $this->getRepository()->getContentService()->loadContent( $location->contentId );
        $search = trim($request->get('s'));

        $result = $this->getPager($request, $content, $search);

        $params = $params + array(
            'activeSearchTags' => $result['activeSearchTags'],
            'resources' => $result['pager'],
            'tagCloud' => $result['tagCloud'],
            'searchText' => $search
        );

        $response = $this->container->get( 'ez_content' )->viewLocation( $locationId, $viewType, $layout, $params );

        $response->headers->set( 'X-Location-Id', $locationId );
        $response->setSharedMaxAge( 86400 );

        return $response;
    }


    /**
     * Gets a pagerfanta object based on request and current content.
     */
    private function getPager(Request $request, $content, $search): array
    {
        $tags = trim($content->getFieldValue('tags')->text);
        $publicIdPrefix = trim($content->getFieldValue('publicid_prefix')->text);
        $searchTags = $request->get('tags', []);
        if (!is_array($searchTags)) {
            $searchTags = [];
        }

        if ($tags) {
            $tags = explode(',', $tags);
        } else {
            $tags = [];
        }

        $pager = $this->storageManager->getResources($tags, $searchTags, $search, $publicIdPrefix);
        $tagCloud = $this->storageManager->getTagCloud($tags, $searchTags, $search, $publicIdPrefix);
        $pager->setCurrentPage((int)$request->get('page', 1));
        $maxResultCount = $this->getConfigResolver()->getParameter('cloudinary_meta_indexer.resources_per_page');
        $pager->setMaxPerPage($maxResultCount);

        return [
            'activeSearchTags' => $searchTags,
            'pager' => $pager,
            'tagCloud' => $tagCloud
        ];
    }
}
