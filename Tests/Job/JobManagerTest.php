<?php
use Elasticsearch\Client as ElasticClient;
use Keboola\Encryption\EncryptorInterface;
use Keboola\StorageApi\Client as SapiClient;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Syrup\ComponentBundle\Job\Metadata\Job;
use Syrup\ComponentBundle\Job\Metadata\JobManager;

/**
 * Created by Miroslav Čillík <miro@keboola.com>
 * Date: 11/06/14
 * Time: 16:36
 */

class JobManagerTest extends WebTestCase
{
	protected static $component = 'syrup-component-bundle';

	/** @var JobManager */
	protected static $jobManager;

	/** @var SapiClient */
	protected static $sapiClient;

	/** @var EncryptorInterface */
	protected static $encryptor;

	/** @var ElasticClient */
	protected static $elasticClient;

	public static function setUpBeforeClass()
	{
		self::$kernel = static::createKernel();
		self::$kernel->boot();

		self::$elasticClient = self::$kernel->getContainer()->get('syrup.elasticsearch');

		self::$jobManager = self::$kernel->getContainer()->get('syrup.job_manager');

		self::$sapiClient = new SapiClient([
			'token'     => self::$kernel->getContainer()->getParameter('storage_api.test.token'),
			'url'       => self::$kernel->getContainer()->getParameter('storage_api.test.url'),
			'userAgent' => 'syrup-component-bundle-test',
		]);

		self::$encryptor = self::$kernel->getContainer()->get('syrup.encryptor');

		// clear data
		$sapiData = self::$sapiClient->getLogData();
		$projectId = $sapiData['owner']['id'];

		$jobs = self::$jobManager->getJobs($projectId, self::$component);
		foreach ($jobs as $job) {
			self::$elasticClient->delete([
				'index' => $job['_index'],
				'type'  => $job['_type'],
				'id'    => $job['id']
			]);
		}
	}

	private function createJob()
	{
		$tokenData = self::$sapiClient->verifyToken();

		return new Job([
			'id'        => self::$sapiClient->generateId(),
			'runId'     => self::$sapiClient->generateId(),
			'project'   => [
				'id'        => $tokenData['owner']['id'],
				'name'      => $tokenData['owner']['name']
			],
			'token'     => [
				'id'            => $tokenData['id'],
				'description'   => $tokenData['description'],
				'token'         => self::$encryptor->encrypt(self::$sapiClient->getTokenString())
			],
			'component' => self::$component,
			'command'   => 'run',
			'process'   => [
				'host'  => 'test',
				'pid'   => posix_getpid()
			],
			'createdTime'   => date('c')
		]);
	}

	private function assertJob(Job $job, $resJob)
	{
		$this->assertEquals($job->getId(), $resJob['id']);
		$this->assertEquals($job->getRunId(), $resJob['runId']);
		$this->assertEquals($job->getLockName(), $resJob['lockName']);

		$this->assertEquals($job->getProject()['id'], $resJob['project']['id']);
		$this->assertEquals($job->getProject()['name'], $resJob['project']['name']);

		$this->assertEquals($job->getToken()['id'], $resJob['token']['id']);
		$this->assertEquals($job->getToken()['description'], $resJob['token']['description']);
		$this->assertEquals($job->getToken()['token'], $resJob['token']['token']);

		$this->assertEquals($job->getComponent(), $resJob['component']);
		$this->assertEquals($job->getStatus(), $resJob['status']);
	}

	public function testIndexJob()
	{
		$job = $this->createJob();
		$id = self::$jobManager->indexJob($job);

		$res = self::$elasticClient->get(array(
			'index' => self::$jobManager->getIndexCurrent(),
			'type'  => 'jobs',
			'id'    => $id
		));

		$resJob = $res['_source'];

		$this->assertJob($job, $resJob);
	}

	public function testUpdateJob()
	{
		$newJob = $this->createJob();

		$id = self::$jobManager->indexJob($newJob);


		$job = self::$jobManager->getJob($id);

		$job->setStatus(Job::STATUS_CANCELLED);

		self::$jobManager->updateJob($job);

		$job = self::$jobManager->getJob($id);

		$this->assertEquals($job->getStatus(), Job::STATUS_CANCELLED);


		$job->setStatus(Job::STATUS_WARNING);

		self::$jobManager->updateJob($job);

		$job = self::$jobManager->getJob($id);

		$this->assertEquals($job->getStatus(), Job::STATUS_WARNING);
	}

	public function testGetJob()
	{
		$job = $this->createJob();
		$id = self::$jobManager->indexJob($job);

		$resJob = self::$jobManager->getJob($id);

		$this->assertJob($job, $resJob->getData());
	}

	public function testGetJobs()
	{
		$job = $this->createJob();
		self::$jobManager->indexJob($job);

		$job2 = $this->createJob();
		self::$jobManager->indexJob($job2);

		$retries = 0;

		$res = [];
		while ($retries < 7) {
			$delaySecs = 2 * pow(2, $retries);
			sleep($delaySecs);
			$retries++;

			$projectId = $job->getProject()['id'];
			$res = self::$jobManager->getJobs($projectId, self::$component);
			if (count($res) >= 2) {
				break;
			}
		}

		$job1Asserted = false;
		$job2Asserted = false;

		foreach ($res as $r) {
			if ($r['id'] == $job->getId()) {
				$this->assertJob($job, $r);
				$job1Asserted = true;
			}
			if ($r['id'] == $job2->getId()) {
				$this->assertJob($job2, $r);
				$job2Asserted = true;
			}
		}

		$this->assertTrue($job1Asserted);
		$this->assertTrue($job2Asserted);
	}
}
