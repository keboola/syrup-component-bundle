<?php
/**
 * Created by Miroslav Čillík <miro@keboola.com>
 * Date: 28/05/14
 * Time: 17:25
 */

namespace Syrup\ComponentBundle\Command;

use Keboola\StorageApi\ClientException;
use Keboola\StorageApi\Table;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Syrup\ComponentBundle\Job\Job;


class DummyRunCommand extends JobCommand
{
	protected $componentName = 'ex-dummy';

	protected function configure()
	{
		$this
			->setName($this->componentName . ':run')
			->setDescription('Dummy Run command')
			->addArgument(
				'jobId',
				InputArgument::REQUIRED,
				'ID of the jbo'
			)
		;
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		/** RUN */
		$data = array(
			array('id', 'col1', 'col2', 'col3'),
			array('1', 'a', 'b', 'c'),
			array('2', 'd', 'e', 'f'),
			array('3', 'g', 'h', 'i'),
			array('4', 'j', 'k', 'l'),
		);

		try {
			$this->sapiClient->createBucket($this->componentName, 'in', 'Data bucket for Dummy Extractor');
		} catch (ClientException $e) {
			// do nothing bucket exists
		}

		$outTable = 'in.c-' . $this->componentName . '.dummy';
		if (isset($params['outputTable'])) {
			$outTable = $params['outputTable'];
		}

		$table = new Table($this->sapiClient, $outTable);
		$table->setFromArray($data, $hasHeader = true);

		/** END */

		// update job to 'success' or 'error'
		$this->job->setStatus(Job::STATUS_SUCCESS);
		$this->jobManager->updateJob($this->job);
	}
}
