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
	/**
	 * @var SyrupS3Uploader
	 */
	protected $_uploader;

	public function setUp()
	{
		$this->assertFileExists($_SERVER['PARAMETERS_FILE'], 'File parameters.yml does not exist. See Resources/config/parameters.default.yml for template.');

		$yaml = new Symfony\Component\Yaml\Parser();
		$paramsYaml = $yaml->parse(file_get_contents($_SERVER['PARAMETERS_FILE']));

		$this->_uploader = new SyrupS3Uploader(array(
			"aws-access-key" => $paramsYaml['parameters']['uploader.aws-access-key'],
			"aws-secret-key" => $paramsYaml['parameters']['uploader.aws-secret-key'],
			"s3-upload-path" => $paramsYaml['parameters']['uploader.s3-upload-path'],
			"bitly-login"    => $paramsYaml['parameters']['uploader.bitly-login'],
			"bitly-api-key"  => $paramsYaml['parameters']['uploader.bitly-api-key']
		));
	}

	public function testMonologUploaderString()
	{
		$testString = 'Body of the error message. bla bla blaaaaa.';
		$url = $this->_uploader->uploadString('SyrupTestFile', $testString);
		$resultString = file_get_contents($url);

		$this->assertEquals($testString, $resultString);
	}

	public function testMonologUploaderFile()
	{
		$testString = 'Body of the error message. bla bla blaaaaa.';
		$filePath = tempnam(sys_get_temp_dir(), 'SyrupTestFile');
		file_put_contents($filePath, $testString);
		$url = $this->_uploader->uploadFile($filePath);
		$resultString = file_get_contents($url);

		$this->assertEquals($testString, $resultString);
	}

}
