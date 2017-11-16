<?php
/**
 * Created by PhpStorm.
 * User: petterkjelkenes
 * Date: 16.11.2017
 * Time: 09.50
 */

namespace Keyteq\Bundle\CloudinaryMetaIndexer\Adapter;


interface AdapterInterface
{

    /**
     * Should return a iterable result which then returns a iterable containing items.
     *
     * @return array|\ArrayIterator
     */
    public function getResources();

}