<?php
/**
 * Created by Miroslav Čillík <miro@keboola.com>
 * Date: 06/06/14
 * Time: 14:50
 */

namespace Syrup\ComponentBundle\Job;

use Syrup\ComponentBundle\Job\Metadata\Job;

class Executor implements ExecutorInterface
{
	public function execute(Job $job)
	{
		print "doing stuff";
	}
}