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

class ComponentCommand extends ContainerAwareCommand
{
    protected $componentName;

    protected $temp;

    /**
     * @TODO: refactor to use COnfig Object
     * @param $componentName
     */
    protected function initSharedConfig($componentName)
    {
        $components = $this->getContainer()->getParameter('components');
        if (isset($components[$componentName]['shared_sapi']['token'])) {
            $token = $components[$componentName]['shared_sapi']['token'];
            $url = null;
            if (isset($components[$componentName]['shared_sapi']['url'])) {
                $url = $components[$componentName]['shared_sapi']['url'];
            }
            $sharedSapi = new Client($token, $url);
            $this->getContainer()->set('shared_sapi', $sharedSapi);
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

    protected function configure()
    {
        if (empty($this->componentName)) {
            throw new \Exception("Missing component name", 500);
        }
        $this->initSharedConfig($this->componentName);


    }

} 