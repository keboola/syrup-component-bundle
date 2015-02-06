<?php
/**
 * @package syrup-component-bundle
 * @copyright 2015 Keboola
 * @author Jakub Matejka <jakub@keboola.com>
 */

namespace SyrupComponentBundle\Tests\Aws\S3;

use Keboola\Temp\Temp;
use Syrup\ComponentBundle\Aws\S3\Uploader;

class UploaderTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @covers Syrup\ComponentBundle\Aws\S3\Uploader::getClient
     * @covers Syrup\ComponentBundle\Aws\S3\Uploader::uploadString
     * @covers Syrup\ComponentBundle\Aws\S3\Uploader::uploadFile
     */
    public function testS3Uploader()
    {
        $s3Uploader = new Uploader([
            'aws-access-key' => SYRUP_AWS_KEY,
            'aws-secret-key' => SYRUP_AWS_SECRET,
            's3-upload-path' => SYRUP_S3_BUCKET
        ]);

        $fileName = uniqid();
        $resultUrl = $s3Uploader->uploadString($fileName, uniqid());
        $this->assertStringStartsWith('https://connection.keboola.com/admin/utils/logs?file=', $resultUrl);
        $this->assertStringEndsWith($fileName, $resultUrl);

        $temp = new Temp();
        $fileInfo = $temp->createTmpFile();
        $file = $fileInfo->openFile('a');
        $file->fwrite(uniqid());
        $resultUrl = $s3Uploader->uploadFile($fileInfo->getRealPath());
        $this->assertStringStartsWith('https://connection.keboola.com/admin/utils/logs?file=', $resultUrl);
        $this->assertStringEndsWith($fileInfo->getFilename(), $resultUrl);
    }
}
