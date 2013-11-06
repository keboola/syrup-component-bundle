<?php
/**
 * Created by PhpStorm.
 * User: mirocillik
 * Date: 05/11/13
 * Time: 14:54
 */

namespace Syrup\ComponentBundle\Filesystem;


class TempServiceFactory
{
    protected $components;

    public function __construct($components = array())
    {
        $this->components = $components;
    }

    public function get($componentName)
    {
        return new TempService($componentName);
    }

} 