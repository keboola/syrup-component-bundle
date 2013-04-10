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

class SyrupS3Uploader
{
	/**
	 * @var array aws-access-key, aws-secret-key, s3-upload-path, bitly-login, bitly-api-key
	 */
	protected $_config;

	/**
	 * @var S3Client
	 */
	protected $_s3;

	public function __construct($config)
	{
		$this->_config = $config;

		$this->_s3 = $this->_getS3();
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
			throw new \Exception('File not found');
		}

		$result = $this->uploadString($name, $fp, $contentType);
		fclose($fp);

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
		$s3FileName = $this->_fileUniquePrefix() . $name;
		$s3Path = $this->_config['s3-upload-path'] . '/' . $s3FileName;

		$this->_s3->putObject(array(
			'Bucket' => $this->_config['s3-upload-path'],
			'Key'    => $s3FileName,
			'Body'   => $content,
			'ACL'    => CannedAcl::PUBLIC_READ,
			'ContentType'   => $contentType
		));
		$url = 'https://s3.amazonaws.com/' . $s3Path;

		try {
			return $this->_shortenUrl($url);
		} catch (\Exception $e) {
			return $url;
		}
	}

	protected function _fileUniquePrefix()
	{
		return date('Y/m/') . date('Y-m-d-H-i-s') . '-' . uniqid() . '-';
	}

	protected function _shortenUrl($url)
	{
		$client = new Client(
			'http://api.bitly.com/v3',
			array(
				'request.params' => array(
					'login'     => $this->_config['bitly-login'],
					'apiKey'    => $this->_config['bitly-api-key'],
					'longUrl'   => $url,
					'format'    => 'json'
				)
			)
		);
		$request = $client->get('shorten');
		$response = $request->send();

		$body = $response->json();

		if (!isset($body['data']) || !isset($body['data']['url'])) {
			throw new \Exception('Bit.ly url not returned');
		}

		return $body['data']['url'];
	}

	/**
	 * @return S3Client
	 */
	protected function _getS3()
	{
		$s3 = S3Client::factory(array(
			'key' => $this->_config['aws-access-key'],
			'secret' => $this->_config['aws-secret-key']
		));

		return $s3;
	}


}
