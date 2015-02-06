<?php
/**
 * Created by Miroslav Čillík <miro@keboola.com>
 * Date: 19/02/14
 * Time: 17:10
 */

namespace Syrup\Filesystem;

use SplFileInfo;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Syrup\ComponentBundle\Filesystem\Temp;

class TempTest extends WebTestCase
{
    protected static $prefix = 'test';

    /** @var Temp */
    protected static $temp;

    public static function setUpBeforeClass()
    {
        self::$temp = new Temp(self::$prefix);
    }

    public function testInitRunFolder()
    {
        self::$temp->initRunFolder();

        $tmpFolder = self::$temp->getTmpFolder();

        $this->assertFileExists($tmpFolder, 'Temp Run Folder not exists');
    }


    public function testCreateTmpFile()
    {
        /** @var SplFileInfo $fileInfo */
        $fileInfo = self::$temp->createTmpFile();

        $this->assertFileExists($fileInfo->getPathname(), 'Temp file does not exists');
    }

    public function testCreateFile()
    {
        $filename = 'testTempFile';

        /** @var SplFileInfo $fileInfo */
        $fileInfo = self::$temp->createFile($filename);

        $this->assertFileExists($fileInfo->getPathname(), 'Temp named file does not exists');
    }
}
