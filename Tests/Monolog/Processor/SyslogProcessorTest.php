<?php
/**
 * @package syrup-component-bundle
 * @copyright 2015 Keboola
 * @author Jakub Matejka <jakub@keboola.com>
 */

namespace SyrupComponentBundle\Tests\Monolog\Processor;

use Syrup\ComponentBundle\Aws\S3\Uploader;
use Symfony\Component\HttpFoundation\Request;
use Syrup\ComponentBundle\Monolog\Processor\SyslogProcessor;
use Syrup\ComponentBundle\Service\StorageApi\StorageApiService;
use Syrup\ComponentBundle\Tests\Monolog\TestCase;

class SyslogProcessorTest extends TestCase
{

    /**
     * @covers Syrup\ComponentBundle\Monolog\Processor\SyslogProcessor::__invoke
     * @covers Syrup\ComponentBundle\Monolog\Processor\SyslogProcessor::processRecord
     */
    public function testProcessor()
    {
        $s3Uploader = new Uploader([
            'aws-access-key' => SYRUP_AWS_KEY,
            'aws-secret-key' => SYRUP_AWS_SECRET,
            's3-upload-path' => SYRUP_S3_BUCKET
        ]);

        $request = new Request();
        $request->headers->add(['x-storageapi-token' => SYRUP_SAPI_TEST_TOKEN]);
        $storageApiService = new StorageApiService();
        $storageApiService->setRequest($request);

        $processor = new SyslogProcessor(SYRUP_APP_NAME, $storageApiService, $s3Uploader);
        $record = $processor($this->getRecord());
        $this->assertArrayHasKey('component', $record);
        $this->assertEquals(SYRUP_APP_NAME, $record['component']);
        $this->assertArrayHasKey('pid', $record);
        $this->assertArrayHasKey('priority', $record);
        $this->assertArrayHasKey('runId', $record);
        $this->assertArrayHasKey('token', $record);
        $this->assertArrayHasKey('id', $record['token']);
        $this->assertArrayHasKey('description', $record['token']);
        $this->assertArrayHasKey('token', $record['token']);
        $this->assertArrayHasKey('owner', $record['token']);
        $this->assertArrayHasKey('id', $record['token']['owner']);
        $this->assertArrayHasKey('name', $record['token']['owner']);
    }
}
