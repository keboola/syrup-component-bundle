<?php
/**
 * Loader.php
 *
 * @author: Miroslav Čillík <miro@keboola.com>
 * @created: 26.9.13
 */

namespace Syrup\ComponentBundle\Routing;

use Symfony\Component\Config\Loader\Loader as BaseLoader;
use Symfony\Component\HttpKernel\HttpKernel;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Routing\Loader\YamlFileLoader;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class Loader extends BaseLoader
{
    /** @var array */
    protected $components;

    public function __construct($components)
    {
        $this->components = $components;
    }

    /**
     * Loads a resource.
     *
     * @param mixed $resource The resource
     * @param string $type     The resource type
     * @return \Symfony\Component\Routing\RouteCollection
     */
    public function load($resource, $type = null)
    {
        $collection = new RouteCollection();

        if ($this->components != null) {
            foreach ($this->components as $componentName => $component) {
                if (!isset($component['bundle'])) {
                    continue;
                }

                $bundleClassName = preg_replace('/^.*\\\/', '', $component['bundle']);
                $resource = '@' . $bundleClassName . '/Resources/config/routing.yml';
                $type = 'yaml';

                /** @var RouteCollection $importedRoutes */
                $importedRoutes = $this->import($resource, $type);

                foreach ($importedRoutes as $route) {
                    /** @var Route $route */
                    if (!strstr($route->getPath(), $componentName)) {
                        $route->setPath('/'. $componentName . $route->getPath());
                    }
                }

                $collection->addCollection($importedRoutes);
            }
        }

        return $collection;
    }

    /**
     * Returns true if this class supports the given resource.
     *
     * @param mixed $resource A resource
     * @param string $type     The resource type
     *
     * @return Boolean true if this class supports the given resource, false otherwise
     */
    public function supports($resource, $type = null)
    {
        return $type === 'component';
    }
}
