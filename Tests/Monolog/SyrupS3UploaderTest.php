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
			"aws-access-key" => "AKIAJJKM4R26QUNBFPTA",
	        "aws-secret-key" => "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",
	        "s3-upload-path" => "keboola-logs/debug-files",
	        "bitly-login"    => "petrsimecek",
	        "bitly-api-key"  => "R_35ed95e18188c58f7b42787f358857ea"
		));

		$url = $uploader->uploadString("This is a test error message - testing error uploading", "Body of the error message. bla bla blaaaaa.");

		return $url;
	}

}
