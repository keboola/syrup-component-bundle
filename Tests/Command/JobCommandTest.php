<?php
/**
 * Created by Miroslav Čillík <miro@keboola.com>
 * Date: 23/10/14
 * Time: 16:53
 */

use Keboola\StorageApi\Client as SapiClient;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\HttpKernel\KernelInterface;
use Syrup\ComponentBundle\Command\JobCommand;
use Syrup\ComponentBundle\Job\Metadata\Job;
use Syrup\ComponentBundle\Job\Metadata\JobManager;

class JobCommandTest extends KernelTestCase
{
	/** @var SapiClient */
	protected $sapi;

	public function testRunjob()
	{
		$kernel = $this->createKernel();
		$kernel->boot();

		/** @var JobManager $jobManager */
		$jobManager = $kernel->getContainer()->get('syrup.job_manager');

		$token = $kernel->getContainer()->getParameter('storage_api.test.token');

		$this->sapi = new SapiClient([
			'token' => $token,
			'userAgent' => 'Syrup Component Bundle TEST'
		]);

		$encryptedToken = $kernel->getContainer()->get('syrup.encryptor')->encrypt($token);

		// job execution test
		$jobId = $jobManager->indexJob($this->createJob($encryptedToken));

		$application = new Application($kernel);
		$application->add(new JobCommand());

		$command = $application->find('syrup:run-job');
		$commandTester = new CommandTester($command);
		$commandTester->execute(
			array(
				'jobId'   => $jobId
			)
		);

		$this->assertEquals(0, $commandTester->getStatusCode());

		$job = $jobManager->getJob($jobId);
		$this->assertEquals($job->getStatus(), Job::STATUS_SUCCESS);

		// replace executor with testing executor
		$kernel->getContainer()->set('syrup.job_executor', new \Syrup\ComponentBundle\Tests\Job\Executor());

		$jobId = $jobManager->indexJob($this->createJob($encryptedToken));

		$application = new Application($kernel);
		$application->add(new JobCommand());

		$command = $application->find('syrup:run-job');
		$commandTester = new CommandTester($command);
		$commandTester->execute(
			array(
				'jobId'   => $jobId
			)
		);

		$this->assertEquals(0, $commandTester->getStatusCode());

		$job = $jobManager->getJob($jobId);
		$this->assertArrayHasKey('testing', $job->getResult());
		$this->assertEquals($job->getStatus(), Job::STATUS_WARNING);
	}

	protected function createJob($token)
	{
		return new Job([
			'id'    => $this->sapi->generateId(),
			'runId'     => $this->sapi->generateId(),
			'project'   => [
				'id'        => '123',
				'name'      => 'Syrup Component Bundle TEST'
			],
			'token'     => [
				'id'            => '123',
				'description'   => 'fake token',
				'token'         => $token
			],
			'component' => 'syrup',
			'command'   => 'run',
			'params'    => [],
			'process'   => [
				'host'  => gethostname(),
				'pid'   => getmypid()
			],
			'createdTime'   => date('c')
		]);
	}
}
