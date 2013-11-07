<?php
/**
 * Created by PhpStorm.
 * User: mirocillik
 * Date: 05/11/13
 * Time: 13:37
 */

namespace Syrup\ComponentBundle\Command;


use Keboola\StorageApi\Client;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

abstract class ComponentCommand extends ContainerAwareCommand
{
    protected $componentName = "abstract";

    protected $temp;

    /**
     * @TODO: refactor to use COnfig Object
     * @throws \Exception
     * @internal param $componentName
     * @return \Keboola\StorageApi\Client
     */
    protected function getSharedConfig()
    {
        $components = $this->getContainer()->getParameter('components');
        if (isset($components[$this->componentName]['shared_sapi']['token'])) {
            $token = $components[$this->componentName]['shared_sapi']['token'];
            $url = null;
            if (isset($components[$this->componentName]['shared_sapi']['url'])) {
                $url = $components[$this->componentName]['shared_sapi']['url'];
            }
            return new Client($token, $url);
        } else {
            throw new \Exception("Shared Config not configured for this component " . $this->componentName, 400);
        }
    }

    public function setName($name)
    {
        parent::setName($this->componentName . ':' . $name);
    }

    protected function getTemp()
    {
        if ($this->temp == null) {
            $this->temp = $this->getContainer()->get('syrup.temp_factory')->get($this->componentName);
        }

        return $this->temp;
    }

} 