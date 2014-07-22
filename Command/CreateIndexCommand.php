<?php
/**
 * Created by Miroslav Čillík <miro@keboola.com>
 * Date: 22/07/14
 * Time: 15:22
 */

namespace Syrup\ComponentBundle\Command;


use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Syrup\ComponentBundle\Job\Metadata\JobManager;

class CreateIndexCommand extends ContainerAwareCommand
{
	protected function configure()
	{
		$this
			->setName('syrup:create-index')
			->setDescription('Create new elasticsearch index')
		;
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		/** @var JobManager $jobManager */
		$jobManager = $this->getContainer()->get('syrup.job_manager');

		$index = $jobManager->createIndex();

		echo "New index '" . $index ."' created.";
	}

}
