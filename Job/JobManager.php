<?php
/**
 * Created by Miroslav Čillík <miro@keboola.com>
 * Date: 28/05/14
 * Time: 14:29
 */

namespace Syrup\ComponentBundle\Job;

use Elasticsearch\Client as ElasticsearchClient;
use Syrup\ComponentBundle\Exception\ApplicationException;

class JobManager
{
	const INDEX = '_syrup_current';

	const PAGING = 9999;

	/** @var ElasticsearchClient */
	protected $client;

	protected $config;

	public function __construct(ElasticsearchClient $client, array $config)
	{
		$this->client = $client;
		$this->config = $config;
	}

	/**
	 * @param JobInterface $job
	 * @return string jobId
	 */
	public function indexJob(JobInterface $job)
	{
		$job->validate();

		$jobData = array(
			'index' => $this->getIndex(),
			'type'  => $this->getType($job->getComponent()),
			'id'    => $job->getId(),
			'body'  => $job->getData()
		);

		$response = $this->client->index($jobData);

		if (!$response['ok']) {
			$e = new ApplicationException("Unable to index job");
			$e->setData(array(
				'job'   => $jobData
			));
			throw $e;
		}

		return $response['_id'];
	}

	/**
	 * @param JobInterface $job
	 * @return string jobId
	 */
	public function updateJob(JobInterface $job)
	{
		$job->validate();

		$jobData = array(
			'index' => $this->getIndex(),
			'type'  => $this->getType($job->getComponent()),
			'id'    => $job->getId(),
			'body'  => array(
				'doc'   => $job->getData()
			)
		);

		$response = $this->client->update($jobData);

		if (!$response['ok']) {
			$e = new ApplicationException("Unable to update job");
			$e->setData(array(
				'job'   => $jobData
			));
			throw $e;
		}

		return $response['_id'];
	}

	public function getJobData($jobId, $component=null)
	{
		$type = $this->getType($component);

		$params = array();
		$params['index'] = $this->getIndex();
		$params['type'] = (is_null($type))?'jobs_*':$type;
		$params['body']['query']['match']['id'] = $jobId;

		$results = $this->client->search($params);

		return $results['hits']['hits'][0]['_source'];
	}

	protected function getType($component)
	{
		return 'jobs_' . $component;
	}

	public function getIndex()
	{
		return $this->config['index_prefix'] . self::INDEX;
	}

}
