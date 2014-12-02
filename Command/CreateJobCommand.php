<?php
/**
 * Created by Miroslav Čillík <miro@keboola.com>
 * Date: 18/09/14
 * Time: 12:34
 */

namespace Syrup\ComponentBundle\Command;

use Keboola\Encryption\EncryptorInterface;
use Keboola\StorageApi\Client as SapiClient;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Syrup\ComponentBundle\Job\Metadata\Job;
use Syrup\ComponentBundle\Job\Metadata\JobManager;
use Syrup\ComponentBundle\Service\Queue\QueueService;

class CreateJobCommand extends ContainerAwareCommand
{
	/** @var SapiClient */
	private $storageApi;

	/** @var EncryptorInterface $encryptor */
	private $encryptor;

	/** @var JobManager */
	private $jobManager;

	private $componentName;

	protected function configure()
	{
		$this
			->setName('syrup:create-job')
			->setDescription('Command to execute jobs')
			->addArgument('token', InputArgument::REQUIRED, 'SAPI token')
			->addArgument('component', InputArgument::REQUIRED, 'Component name')
			->addArgument('cmd', InputArgument::REQUIRED, 'Job command name')
			->addArgument('params', InputArgument::OPTIONAL, 'Job command parameters as JSON', '{}')
			->addOption('no-run', 'norun', InputOption::VALUE_NONE, "Dont run the job, just create it")
		;
	}

	protected function initialize(InputInterface $input, OutputInterface $output)
	{
		$token = $input->getArgument('token');
		$this->componentName = $input->getArgument('component');

		$this->storageApi = new SapiClient([
			'url'       => $this->getContainer()->getParameter('storage_api.url'),
			'token'     => $token,
			'userAgent' => $this->componentName
		]);

		$this->encryptor = $this->getContainer()->get('syrup.encryptor');

		$this->jobManager = $this->getContainer()->get('syrup.job_manager');
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$command = $input->getArgument('cmd');
		$params = json_decode($input->getArgument('params'), true);

		// Create new job
		/** @var Job $job */
		$job = $this->createJob($command, $params);

		// Add job to Elasticsearch
		$jobId = $this->jobManager->indexJob($job);

		$output->writeln('Created job id ' . $jobId);

		// Run Job
		if (!$input->getOption('no-run')) {
			$runJobCommand = $this->getApplication()->find('syrup:run-job');

			$returnCode = $runJobCommand->run(
				new ArrayInput([
					'command'   => 'syrup:run-job',
					'jobId'     => $jobId
				]),
				$output
			);

			if ($returnCode == 0) {
				$output->writeln('Job successfully executed');
			} elseif ($returnCode == 2 || $returnCode == 64) {
				$output->writeln('DB is locked. Run job later using syrup:run-job');
			} else {
				$output->writeln('Error occured');
			}
		}

		return 0;
	}

	protected function createJob($command, $params)
	{
		$tokenData = $this->storageApi->verifyToken();

		return new Job([
			'id'        => $this->storageApi->generateId(),
			'runId'     => $this->storageApi->generateRunId(),
			'project'   => [
				'id'        => $tokenData['owner']['id'],
				'name'      => $tokenData['owner']['name']
			],
			'token'     => [
				'id'            => $tokenData['id'],
				'description'   => $tokenData['description'],
				'token'         => $this->encryptor->encrypt($this->storageApi->getTokenString())
			],
			'component' => $this->componentName,
			'command'   => $command,
			'params'    => $params,
			'process'   => [
				'host'  => gethostname(),
				'pid'   => getmypid()
			],
			'createdTime'   => date('c')
		]);
	}

	protected function enqueue($jobId, $queueName = 'default', $otherData = [])
	{
		$data = [
			'jobId'     => $jobId,
			'component' => $this->componentName
		];

		if (count($otherData)) {
			$data = array_merge($data, $otherData);
		}

		/** @var QueueService $queue */
		$queue = $this->getContainer()->get('syrup.queue_factory')->get($queueName);
		$queue->enqueue($data);
	}
}
