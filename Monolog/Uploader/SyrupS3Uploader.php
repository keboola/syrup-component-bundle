<?php
/**
 * SyrupS3Uploader.php
 *
 * @author: Miroslav Čillík <miro@keboola.com>
 * @created: 22.1.13
 */

namespace Syrup\ComponentBundle\Monolog\Uploader;

use Aws\S3\Enum\CannedAcl;
use Aws\S3\S3Client;
use Aws\Common\Aws;
use Guzzle\Http\Client;
use Monolog\Logger;

class SyrupS3Uploader
{
	/**
	 * @var array aws-access-key, aws-secret-key, s3-upload-path, bitly-login, bitly-api-key
	 */
	protected $config;

	/** @var S3Client */
	protected $s3;

	/** @var Logger */
	protected $log;

	public function __construct($config, Logger $log)
	{
		$this->config = $config;
		$this->log = $log;

		$this->s3 = $this->getS3();
	}

	/**
	 * @param string $filePath Path to File
	 * @param string $contentType Content Type
	 * @param bool $shortenUrl
	 * @return string
	 * @throws \Exception
	 */
	public function uploadFile($filePath, $contentType = 'text/plain', $shortenUrl = true)
	{
		$name = basename($filePath);
		$fp = fopen($filePath, 'r');
		if (!$fp) {
			throw new \Exception('File not found');
		}

		$result = $this->uploadString($name, $fp, $contentType, $shortenUrl);
		if (is_resource($fp)) {
			fclose($fp);
		}

		return $result;
	}

	/**
	 * @param string $name File Name
	 * @param string $content File Content
	 * @param string $contentType Content Type
	 * @param bool $shortenUrl
	 * @return string
	 */
	public function uploadString($name, $content, $contentType = 'text/plain', $shortenUrl = true)
	{
		$s3FileName = $this->fileUniquePrefix() . $name;
		$s3Path = $this->config['s3-upload-path'] . '/' . $s3FileName;

		$this->s3->putObject(array(
			'Bucket' => $this->config['s3-upload-path'],
			'Key'    => $s3FileName,
			'Body'   => $content,
			'ACL'    => CannedAcl::PUBLIC_READ,
			'ContentType'   => $contentType
		));
		$url = 'https://s3.amazonaws.com/' . $s3Path;

		if ($shortenUrl) {
			try {
				return $this->shortenUrl($url);
			} catch (\Exception $e) {

				$this->log->error('Exception occured shortening the url', array(
					'message'   => $e->getMessage(),
					'url'   => $url,
					'exception' => $e
				));

				return $url;
			}
		} else {
			return $url;
		}
	}

	protected function fileUniquePrefix()
	{
		return date('Y/m/') . date('Y-m-d-H-i-s') . '-' . uniqid() . '-';
	}

	protected function shortenUrl($url)
	{
		$client = new Client();
		$apiUrl = sprintf('https://api-ssl.bitly.com/shorten?login=%s&apiKey=%s&longUrl=%s&format=json', $this->config['bitly-login'], $this->config['bitly-api-key'], $url);
		$request = $client->get($apiUrl);
		$response = $request->send();

		$body = $response->json();

		if (!isset($body['results']) || !isset($body['results'][$url]['shortUrl'])) {
			throw new \Exception('Bit.ly url not returned');
		}

		return $body['results'][$url]['shortUrl'];
	}

	/**
	 * @return S3Client
	 */
	protected function getS3()
	{
		$s3 = S3Client::factory(array(
			'key' => $this->config['aws-access-key'],
			'secret' => $this->config['aws-secret-key']
		));

		return $s3;
	}


}
