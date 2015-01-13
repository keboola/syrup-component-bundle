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
	const PAGING = 100;

	/** @var ElasticsearchClient */
	protected $client;

	protected $config;

	protected $componentName;

	public function __construct(ElasticsearchClient $client, array $config, $componentName)
	{
		$this->client = $client;
		$this->config = $config;
		$this->componentName = $componentName;
	}

	/**
	 * @param null $mappings
	 * @return string Updated index name
	 */
	public function putMappings($mappings = null)
	{
		$params['index'] = $this->getLastIndex();
		$params['type'] = 'jobs';
		$params['body'] = $mappings;

		$this->client->indices()->putMapping($params);
		return $params['index'];
	}

	public function createIndex($settings = null, $mappings = null)
	{
		// Assemble new index name

		$nextIndexNumber = 1;
		$lastIndexName = $this->getLastIndex();

		if (null != $lastIndexName) {
			$lastIndexNameArr = explode('_', $lastIndexName);
			$nextIndexNumber = array_pop($lastIndexNameArr) + 1;
		}

		$nextIndexName = $this->getIndex() . '_' . date('Y') . '_' . $nextIndexNumber;

		// Create new index
		$params['index'] = $nextIndexName;
		if (null != $settings) {
			$params['body']['settings'] = $settings;
		}
		if (null != $mappings) {
			$params['body']['mappings'] = $mappings;
		}

		$this->client->indices()->create($params);

		// Update aliases
		$params = [];
		$params['body'] = [
			'actions' => [
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

		if (null != $lastIndexName) {
			array_unshift($params['body']['actions'], [
				'remove' => [
					'index' => $lastIndexName,
					'alias' => $this->getIndexCurrent()
				]
			]);
		}

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
			'type'  => 'jobs',
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
			'index' => $job->getIndex(),
			'type'  => $job->getType(),
			'id'    => $job->getId(),
			'body'  => array(
				'doc'   => $job->getData()
			)
		);

		$response = $this->client->update($jobData);

		$this->client->indices()->refresh(array(
			'index' => $job->getIndex()
		));

		return $response['_id'];
	}

	public function getJob($jobId, $component=null)
	{
		$params = [];
		$params['index'] = $this->config['index_prefix'] . '_syrup*';

		if (!is_null($component)) {
			$params['index'] = $this->config['index_prefix'] . '_syrup_' . $component;
		}

		$params['body'] = [
			'size'  => 1,
			'query' => [
				'match_all' => []
			],
			'filter' => [
				'ids' => [
					'values' => [$jobId]
				]
			]
		];

		$result = $this->client->search($params);

		if ($result['hits']['total'] > 0) {
			$job = new Job(
				$result['hits']['hits'][0]['_source'],
				$result['hits']['hits'][0]['_index'],
				$result['hits']['hits'][0]['_type']
			);

			return $job;
		}
		return null;
	}

	public function getJobs(
		$projectId,
		$component = null,
		$runId = null,
		$queryString = null,
		$since = null,
		$until = null,
		$offset=0,
		$limit=self::PAGING
	) {
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

		$rangeFilter = [];
		if ($since != null) {
			if ($until == null) {
				$until = 'now';
			}

			$rangeFilter = [
				'range' => ['createdTime'  => [
					'gte' => date('c', strtotime($since)),
					'lte' => date('c', strtotime($until)),
				]]
			];
		}

		$params = [];
		$params['index'] = $this->config['index_prefix'] . '_syrup_*';

		if (!is_null($component)) {
			$params['index'] = $this->config['index_prefix'] . '_syrup_' . $component;
		}

		if (!empty($rangeFilter)) {
			$filter[] = $rangeFilter;
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
			],
			'sort' => [
				'id' => [
					'order' => 'desc'
				]
			]
		];

		$results = [];
		$hits = $this->client->search($params);

		foreach ($hits['hits']['hits'] as $hit) {
			$res = $hit['_source'];
			$res['_index'] = $hit['_index'];
			$res['_type'] = $hit['_type'];
			$res['id'] = (int) $res['id'];
			$results[] = $res;
		}

		return $results;
	}

	public function getIndex()
	{
		return $this->config['index_prefix'] . '_syrup_' . $this->componentName;
	}

	public function getIndexCurrent()
	{
		return $this->getIndex() . '_current' ;
	}

	public function getIndexPrefix()
	{
		return $this->config['index_prefix'];
	}

	protected function getLastIndex()
	{
		try {
			$indices = $this->client->indices()->getAlias([
				'name'  => $this->getIndex()
			]);

			return IndexNameResolver::getLastIndexName(array_keys($indices));

		} catch (Missing404Exception $e) {
			return null;

		}
	}
}
