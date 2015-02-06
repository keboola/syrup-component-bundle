<?php
/**
 * Created by Miroslav Čillík <miro@keboola.com>
 * Date: 06/06/14
 * Time: 14:50
 */

namespace Syrup\ComponentBundle\Job;

use Keboola\StorageApi\Client as SapiClient;
use Syrup\ComponentBundle\Job\Metadata\Job;

class Executor implements ExecutorInterface
{
    /** @var SapiClient */
    protected $storageApi;

    public function setStorageApi(SapiClient $sapi)
    {
        $this->storageApi = $sapi;
    }


    public function execute(Job $job)
    {
        // do stuff
    }
}
