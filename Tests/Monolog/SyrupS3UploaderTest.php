<?php
/**
 * SyrupS3UploaderTest.php
 *
 * @author: Miroslav Čillík <miro@keboola.com>
 * @created: 23.1.13
 */
use Syrup\ComponentBundle\Monolog\Uploader\SyrupS3Uploader;

class SyrupS3UploaderTest extends \PHPUnit_Framework_TestCase
{
	public function testMonologUploader()
	{
		$uploader = new SyrupS3Uploader(array(
			"aws-access-key" => "***REMOVED***",
	        "aws-secret-key" => "***REMOVED***",
	        "s3-upload-path" => "keboola-logs/debug-files",
	        "bitly-login"    => "***REMOVED***",
	        "bitly-api-key"  => "***REMOVED***"
		));

		$url = $uploader->uploadString("This is a test error message - testing error uploading", "Body of the error message. bla bla blaaaaa.");

		return $url;
	}

}
