<?php

namespace Keyteq\Bundle\CloudinaryMetaIndexer\Templating\Twig\Extension;

use Keyteq\Bundle\CloudinaryMetaIndexer\Document\CloudinaryResource;
use Twig_Extension;
use Twig_SimpleFunction;

class KeyteqCloudinaryMetaIndexerExtension extends Twig_Extension
{
    /**
     * Returns the name of the extension.
     *
     * @return string The extension name
     */
    public function getName()
    {
        return 'keyteq_cloudinary_meta_indexer';
    }

    /**
     * Returns a list of functions to add to the existing list.
     *
     * @return array An array of functions
     */
    public function getFunctions()
    {
        return array(
            new Twig_SimpleFunction(
                'keyteq_cmi_thumbnail',
                array($this, 'getThumbnail'),
                array('is_safe' => array('html'))
            ),
        );
    }

    /**
     * Generates a <img> tag for previewing a resource.
     * @param CloudinaryResource $resource a resource from mongodb.
     * @param array $params Override what you want of cloudinary params here. E.g. width, height, crop etc. See valid params in cloudinary doc for cl_image_tag.
     * @return string html tag representing the resource.
     */
    public function getThumbnail (CloudinaryResource $resource, $params = []) {
        $defaults = array(
            'width' => 400,
            'height' => 400,
            'crop' => 'lfill',
            'resource_type' => $resource->getResourceType()
        );
        $params = array_merge($defaults, $params);
        $file = $resource->getPublicId() . '.jpg';
        return cl_image_tag($file, $params);
    }
}
