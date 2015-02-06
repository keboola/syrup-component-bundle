<?php
/**
 * Created by Miroslav Čillík <miro@keboola.com>
 * Date: 06/06/14
 * Time: 14:59
 */

namespace Syrup\ComponentBundle\Job;

use Keboola\StorageApi\Client;
use Syrup\ComponentBundle\Job\Metadata\Job;

interface ExecutorInterface
{
    public function setStorageApi(Client $sapi);

    /**
     * @param Job $job
     * @return array|Job
     */
    public function execute(Job $job);
}
