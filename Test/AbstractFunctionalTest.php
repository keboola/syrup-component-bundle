<?php
/**
 * @package syrup-component-bundle
 * @copyright 2014 Keboola
 * @author Jakub Matejka <jakub@keboola.com>
 */

namespace Syrup\ComponentBundle\Test;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Keboola\StorageApi\Client as StorageApiClient;
use Syrup\ComponentBundle\Command\JobCommand;
use Syrup\ComponentBundle\Job\Metadata\Job;
use Syrup\ComponentBundle\Job\Metadata\JobManager;

abstract class AbstractFunctionalTest extends WebTestCase
{
    protected $storageApiToken;
    /**
     * @var \Keboola\StorageApi\Client
     */
    protected $storageApiClient;
    /**
     * @var \Symfony\Bundle\FrameworkBundle\Client
     */
    protected $httpClient;
    /**
     * @var CommandTester
     */
    protected $commandTester;
    /**
     * @var JobManager
     */
    protected $jobManager;


    /**
     * Setup HTTP client, command runner and Storage API client for each test
     */
    protected function setUp()
    {
        $this->httpClient = static::createClient();
        $container = $this->httpClient->getContainer();

        if (!$this->storageApiToken) {
            $this->storageApiToken = $container->getParameter('storage_api.test.token');
        }

        $this->httpClient->setServerParameters([
            'HTTP_X-StorageApi-Token' => $this->storageApiToken
        ]);

        $this->jobManager = $container->get('syrup.job_manager');

        $application = new Application($this->httpClient->getKernel());
        $application->add(new JobCommand());
        $command = $application->find('syrup:run-job');
        $this->commandTester = new CommandTester($command);

        $this->storageApiClient = new StorageApiClient([
            'token' => $this->storageApiToken,
            'url' => $container->getParameter('storage_api.test.url')
        ]);
    }

    /**
     * Request to API, return result
     * @param string $url URL of API call
     * @param string $method HTTP method of API call
     * @param array $params parameters of POST call
     * @return array
     */
    protected function callApi($url, $method = 'POST', $params = array())
    {
        $this->httpClient->request($method, $url, [], [], [], json_encode($params));
        $response = $this->httpClient->getResponse();
        /* @var \Symfony\Component\HttpFoundation\Response $response */

        $responseJson = json_decode($response->getContent(), true);
        $this->assertNotEmpty(
            $responseJson,
            sprintf(
                "Response of API call '%s' after json decoding should not be empty. Raw response:\n%s\n",
                $url,
                $response->getContent()
            )
        );

        return $responseJson;
    }


    /**
     * Call API and process job immediately, return job info
     * @param string $url URL of API call
     * @param array $params parameters of POST call
     * @param string $method HTTP method of API call
     * @return Job
     */
    protected function processJob($url, $params = [], $method = 'POST')
    {
        $responseJson = $this->callApi($url, $method, $params);
        $this->assertArrayHasKey('id', $responseJson, sprintf("Response of API call '%s' should contain 'id' key.", $url));
        $this->commandTester->execute([
            'command' => 'syrup:run-job',
            'jobId' => $responseJson['id']
        ]);

        return $this->jobManager->getJob($responseJson['id']);
    }
}
