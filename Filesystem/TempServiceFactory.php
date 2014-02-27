<?php
/**
 * Created by PhpStorm.
 * User: mirocillik
 * Date: 05/11/13
 * Time: 14:54
 */

namespace Syrup\ComponentBundle\Filesystem;


use Syrup\ComponentBundle\Exception\UserException;

class TempServiceFactory
{
    protected $components;

    public function __construct($components = array())
    {
        $this->components = $components;
    }

    public function get($componentName)
    {
	    /** @todo: will be in 1.4 */
//	    if (!array_key_exists($componentName, $this->components)) {
//		    throw new UserException("Component '".$componentName."' does not exist.");
//	    }
        return new TempService($componentName);
    }

}
