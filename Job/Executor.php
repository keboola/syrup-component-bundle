<?php
use Syrup\ComponentBundle\Job\Metadata\Job;

/**
 * Created by Miroslav Čillík <miro@keboola.com>
 * Date: 06/06/14
 * Time: 14:50
 */

class Executor
{
	public function execute(Job $job)
	{
		print "doing stuff";
	}
}
