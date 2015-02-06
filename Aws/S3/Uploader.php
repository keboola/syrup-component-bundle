<?php
/**
 * @package syrup-component-bundle
 * @copyright 2015 Keboola
 * @author Jakub Matejka <jakub@keboola.com>
 */

namespace Syrup\ComponentBundle\Aws\S3;

use Aws\S3\Enum\CannedAcl;
use Keboola\StorageApi\Aws\S3\S3Client;

class Uploader
{
    /**
     * @var S3Client
     */
    private $client;
    private $awsKey;
    private $awsSecret;
    protected $s3Bucket;


    public function __construct($config)
    {
        if (!isset($config['aws-access-key'])) {
            throw new \Exception('Parameter \'aws-access-key\' is missing from config');
        }
        $this->awsKey = $config['aws-access-key'];

        if (!isset($config['aws-secret-key'])) {
            throw new \Exception('Parameter \'aws-secret-key\' is missing from config');
        }
        $this->awsSecret = $config['aws-secret-key'];

        if (!isset($config['s3-upload-path'])) {
            throw new \Exception('Parameter \'s3-upload-path\' is missing from config');
        }
        $this->s3Bucket = $config['s3-upload-path'];
    }

    protected function getClient()
    {
        if (!$this->client) {
            $this->client = S3Client::factory(array(
                'key' => $this->awsKey,
                'secret' => $this->awsSecret
            ));
        }
        return $this->client;
    }

    /**
     * @param string $filePath Path to File
     * @param string $contentType Content Type
     * @return string
     * @throws \Exception
     */
    public function uploadFile($filePath, $contentType = 'text/plain')
    {
        $name = basename($filePath);
        $fp = fopen($filePath, 'r');
        if (!$fp) {
            throw new \Exception("File '$filePath' not found");
        }

        $result = $this->uploadString($name, $fp, $contentType);
        if (is_resource($fp)) {
            fclose($fp);
        }

        return $result;
    }

    /**
     * @param string $name File Name
     * @param string $content File Content
     * @param string $contentType Content Type
     * @return string
     */
    public function uploadString($name, $content, $contentType = 'text/plain')
    {
        $s3FileName = sprintf('%s-%s-%s', date('Y/m/d/Y-m-d-H-i-s'), uniqid(), $name);

        $this->getClient()->putObject(array(
            'Bucket' => $this->s3Bucket,
            'Key' => $s3FileName,
            'Body' => $content,
            'ACL' => CannedAcl::PRIVATE_ACCESS,
            'ContentType' => $contentType
        ));

        return 'https://connection.keboola.com/admin/utils/logs?file=' . $s3FileName;
    }
}
