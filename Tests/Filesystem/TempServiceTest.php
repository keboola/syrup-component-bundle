<?php
/**
 * Created by Miroslav Čillík <miro@keboola.com>
 * Date: 19/02/14
 * Time: 17:10
 */

namespace Syrup\Filesystem;


use SplFileInfo;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Syrup\ComponentBundle\Filesystem\TempService;

class TempServiceTest extends WebTestCase
{
	protected static $prefix = 'test';

	/** @var TempService */
	protected static $tempService;

	public static function setUpBeforeClass()
	{
		self::$tempService = new TempService(self::$prefix);
	}

	public function testInitRunFolder()
	{
		self::$tempService->initRunFolder();

		$tmpFolder = self::$tempService->getTmpFolder();

		$this->assertFileExists($tmpFolder, 'Temp Run Folder not exists');
	}


	public function testCreateTmpFile()
	{
		/** @var SplFileInfo $fileInfo */
		$fileInfo = self::$tempService->createTmpFile();

		$this->assertFileExists($fileInfo->getPathname(), 'Temp file does not exists');
	}

	public function testCreateFile()
	{
		$filename = 'testTempFile';

		/** @var SplFileInfo $fileInfo */
		$fileInfo = self::$tempService->createFile($filename);

		$this->assertFileExists($fileInfo->getPathname(), 'Temp named file does not exists');
	}
}
