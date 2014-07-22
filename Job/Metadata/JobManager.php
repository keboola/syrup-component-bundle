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
	const INDEX = '_syrup';

	const INDEX_CURRENT = '_syrup_current';

	const PAGING = 100;

	/** @var ElasticsearchClient */
	protected $client;

	protected $config;

	public function __construct(ElasticsearchClient $client, array $config)
	{
		$this->client = $client;
		$this->config = $config;
	}

	public function createIndex()
	{
		// Assemble new index name
		$lastIndexName = $this->getLastIndex();
		$lastIndexNameArr = explode('_', $lastIndexName);
		$nextIndexNumber = $lastIndexNameArr[3] + 1;
		$nextIndexName = $this->getIndex() . '_' . date('Y') . '_' . $nextIndexNumber;

		// Create new index
		$this->client->indices()->create([
			'index'  => $nextIndexName
		]);

		// Update aliases
		$params['body'] = [
			'actions' => [
				[
					'remove' => [
						'index' => $lastIndexName,
						'alias' => $this->getIndexCurrent()
					]
				],
				[
					'add' => [
						'index' => $nextIndexName,
						'alias' => $this->getIndexCurrent()
					]
				],
				[
					'add' => [
						'index' => $nextIndexName,
						'alias' => $this->getIndex()
					]
				]
			]
		];

		$this->client->indices()->updateAliases($params);

		return $nextIndexName;
	}

	/**
	 * @param JobInterface $job
	 * @return string jobId
	 */
	public function indexJob(JobInterface $job)
	{
		$job->validate();

		$jobData = array(
			'index' => $this->getIndexCurrent(),
			'type'  => $this->getType($job->getComponent()),
			'id'    => $job->getId(),
			'body'  => $job->getData()
		);

		$response = $this->client->index($jobData);

		if (!$response['created']) {
			$e = new ApplicationException("Unable to index job");
			$e->setData(array(
				'job'   => $jobData
			));
			throw $e;
		}

		$this->client->indices()->refresh(array(
			'index' => $this->getIndexCurrent()
		));

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

		$this->client->indices()->refresh(array(
			'index' => $this->getIndex()
		));

		return $response['_id'];
	}

	public function getJob($jobId, $component=null)
	{
		$params = [];
		$params['index'] = $this->getIndex();

		if (!is_null($component)) {
			$params['type'] = $this->getType($component);
		}

		$params['body'] = [
			'size'  => 1,
			'query' => [
				'match' => ['id' => $jobId]
			]
		];

		$result = $this->client->search($params);

		if ($result['hits']['total'] > 0) {
			return new Job($result['hits']['hits'][0]['_source']);
		}
		return null;
	}

	public function getJobs($projectId, $component = null, $runId = null, $queryString=null, $offset=0, $limit=self::PAGING)
	{
		$filter = [];
		$filter[] = ['term' => ['project.id' => $projectId]];

		if ($runId != null) {
			$filter[] = ['term' => ['runId' => $runId]];
		}

		$query = ['match_all' => []];
		if ($queryString != null) {
			$query = [
				'query_string' => [
					'allow_leading_wildcard' => 'false',
					'default_operator' => 'AND',
					'query' => $queryString
				]
			];
		}

		$params = [];
		$params['index'] = $this->getIndex();

		if (!is_null($component)) {
			$params['type'] = $this->getType($component);
		}

		$params['body'] = [
			'from' => $offset,
			'size' => $limit,
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

	public function getIndexCurrent()
	{
		return $this->config['index_prefix'] . self::INDEX_CURRENT;
	}

	public function getIndexRead()
	{
		return $this->getIndex() . '_*';
	}

	protected function getLastIndex()
	{
		$indices = $this->client->indices()->getAlias([
			'name'  => $this->getIndex()
		]);
		$lastIndexName = key(array_slice($indices, -1, 1));

		return $lastIndexName;
	}
}
