<?php
use Elasticsearch\Client as ElasticClient;
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

		// clear data
		$sapiData = self::$sapiClient->getLogData();
		$projectId = $sapiData['owner']['id'];

		$jobs = self::$jobManager->getJobs($projectId, self::$component);
		foreach ($jobs as $job) {
			self::$elasticClient->delete([
				'index' => self::$jobManager->getIndex(),
				'type'  => 'jobs_' . self::$component,
				'id'    => $job['id']
			]);
		}
	}

	private function createJob()
	{
		$sapiData = self::$sapiClient->getLogData();
		$projectId = $sapiData['owner']['id'];

		$jobId = self::$sapiClient->generateId();
		$runId = self::$sapiClient->generateId();
		$token = self::$sapiClient->getTokenString();
		$component = self::$component;

		$job = new Job([
			'id'    => $jobId,
			'runId' => $runId,
			'projectId' => $projectId,
			'token'     => $token,
			'component' => $component,
			'command'   => 'run'
		]);

		return $job;
	}

	private function assertJob(Job $job, $resJob)
	{
		$this->assertEquals($job->getId(), $resJob['id']);
		$this->assertEquals($job->getRunId(), $resJob['runId']);
		$this->assertEquals($job->getLockName(), $resJob['lockName']);
		$this->assertEquals($job->getProjectId(), $resJob['projectId']);
		$this->assertEquals($job->getToken(), $resJob['token']);
		$this->assertEquals($job->getComponent(), $resJob['component']);
		$this->assertEquals($job->getStatus(), $resJob['status']);
	}

	public function testIndexJob()
	{
		$job = $this->createJob();
		$id = self::$jobManager->indexJob($job);

		$res = self::$elasticClient->get(array(
			'index' => self::$jobManager->getIndex(),
			'type'  => 'jobs_syrup-component-bundle',
			'id'    => $id
		));

		$resJob = $res['_source'];

		$this->assertJob($job, $resJob);
	}

	public function testGetJob()
	{
		$job = $this->createJob();
		$id = self::$jobManager->indexJob($job);

		$resJob = self::$jobManager->getJob($id);

		$this->assertJob($job, $resJob);
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

			$res = self::$jobManager->getJobs($job->getProjectId(), self::$component);
			if (count($res) == 2) {
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

		$this->assertCount(2, $res);
		$this->assertTrue($job1Asserted);
		$this->assertTrue($job2Asserted);
	}
}
