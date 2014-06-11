<?php
/**
 * Created by Miroslav Čillík <miro@keboola.com>
 * Date: 28/05/14
 * Time: 14:29
 */

namespace Syrup\ComponentBundle\Job\Metadata;

use Elasticsearch\Client as ElasticsearchClient;
use Elasticsearch\Common\Exceptions\Missing404Exception;
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

	public function getJob($jobId, $component=null)
	{
		$params = [];
		$params['index'] = $this->getIndex();
		$params['type'] = is_null($component)?'_all':$this->getType($component);
		$params['id'] = $jobId;

		try {
			$result = $this->client->get($params);
			return new Job($result['_source']);
		} catch (Missing404Exception $e) {
			return null;
		}
	}

	public function getJobs($projectId, $component = null, $runId = null, $query=null)
	{

		$filter = [];
		$filter[] = ['term' => ['projectId' => $projectId]];

		if ($runId != null) {
			$filter[] = ['term' => ['runId' => $runId]];
		}

		if ($query == null) {
			$query = ['match_all' => []];
		}

		$params = [];
		$params['index'] = $this->getIndex();
		$params['type'] = $this->getType($component);
		$params['body'] = [
			'size' => self::PAGING,
			'query' => [
				'filtered' => [
					'filter' => [
						'bool' => [
							'must' => $filter
						]
					],
					'query' => $query
				]
			]
		];

		$results = [];
		$hits = $this->client->search($params);
		foreach ($hits['hits']['hits'] as $hit) {
			$results[] = $hit['_source'];
		}

		return $results;
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
