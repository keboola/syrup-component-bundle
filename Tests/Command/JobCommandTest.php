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
use Syrup\ComponentBundle\Tests\Job AS TestExecutor;

class JobCommandTest extends KernelTestCase
{
    /**
     * @var Application
     */
    protected $application;

    /** @var SapiClient */
	protected $storageApiClient;

    protected function setUp()
    {
        $this->createKernel();
        $this->bootKernel();

        $this->storageApiClient = new SapiClient([
            'token' => self::$kernel->getContainer()->getParameter('storage_api.test.token'),
            'userAgent' => 'Syrup Component Bundle TEST'
        ]);

        $this->application = new Application(self::$kernel);
        $this->application->add(new JobCommand());
    }

	public function testRunjob()
	{
		/** @var JobManager $jobManager */
		$jobManager = self::$kernel->getContainer()->get('syrup.job_manager');
		$encryptedToken = self::$kernel->getContainer()->get('syrup.encryptor')->encrypt(self::$kernel->getContainer()->getParameter('storage_api.test.token'));

		// job execution test
		$jobId = $jobManager->indexJob($this->createJob($encryptedToken));

		$command = $this->application->find('syrup:run-job');
		$commandTester = new CommandTester($command);
		$commandTester->execute(
			array(
				'jobId'   => $jobId
			)
		);

		$this->assertEquals(0, $commandTester->getStatusCode());

		$job = $jobManager->getJob($jobId);
		$this->assertEquals($job->getStatus(), Job::STATUS_SUCCESS);

		// replace executor with warning executor
		self::$kernel->getContainer()->set('syrup.job_executor', new TestExecutor\WarningExecutor());

		$jobId = $jobManager->indexJob($this->createJob($encryptedToken));

		$this->application->find('syrup:run-job');
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

		// replace executor with success executor
		self::$kernel->getContainer()->set('syrup.job_executor', new TestExecutor\SuccessExecutor());

		$jobId = $jobManager->indexJob($this->createJob($encryptedToken));

		$this->application->find('syrup:run-job');
		$commandTester = new CommandTester($command);
		$commandTester->execute(
			array(
				'jobId'   => $jobId
			)
		);

		$this->assertEquals(0, $commandTester->getStatusCode());

		$job = $jobManager->getJob($jobId);
		$this->assertArrayHasKey('testing', $job->getResult());
		$this->assertEquals($job->getStatus(), Job::STATUS_SUCCESS);

		// replace executor with error executor
		self::$kernel->getContainer()->set('syrup.job_executor', new TestExecutor\ErrorExecutor());

		$jobId = $jobManager->indexJob($this->createJob($encryptedToken));

		$this->application->find('syrup:run-job');
		$commandTester = new CommandTester($command);
		$commandTester->execute(
			array(
				'jobId'   => $jobId
			)
		);

		$this->assertEquals(0, $commandTester->getStatusCode());

		$job = $jobManager->getJob($jobId);
		$this->assertArrayHasKey('testing', $job->getResult());
		$this->assertEquals($job->getStatus(), Job::STATUS_ERROR);
	}

	public function testRunJobWithHook()
	{
		/** @var JobManager $jobManager */
		$jobManager = self::$kernel->getContainer()->get('syrup.job_manager');
		$encryptedToken = self::$kernel->getContainer()->get('syrup.encryptor')->encrypt(self::$kernel->getContainer()->getParameter('storage_api.test.token'));

		self::$kernel->getContainer()->set('syrup.job_executor', new TestExecutor\HookExecutor($jobManager));

		// job execution test
		$jobId = $jobManager->indexJob($this->createJob($encryptedToken));

		$command = $this->application->find('syrup:run-job');
		$commandTester = new CommandTester($command);
		$commandTester->execute(
			array(
				'jobId'   => $jobId
			)
		);

		$this->assertEquals(0, $commandTester->getStatusCode());

		$job = $jobManager->getJob($jobId);

		$result = $job->getResult();

		$this->assertArrayHasKey('testing', $result);
		$this->assertArrayHasKey(TestExecutor\HookExecutor::HOOK_RESULT_KEY, $result);
		$this->assertEquals(TestExecutor\HookExecutor::HOOK_RESULT_VALUE, $result[TestExecutor\HookExecutor::HOOK_RESULT_KEY]);
		$this->assertEquals($job->getStatus(), Job::STATUS_SUCCESS);
	}

	protected function createJob($token)
	{
		return new Job([
			'id'    => $this->storageApiClient->generateId(),
			'runId'     => $this->storageApiClient->generateId(),
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
