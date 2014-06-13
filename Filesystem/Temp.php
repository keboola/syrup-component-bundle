<?php
/**
 * Created by PhpStorm.
 * User: mirocillik
 * Date: 05/11/13
 * Time: 14:48
 */

namespace Syrup\ComponentBundle\Filesystem;


use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;

class Temp
{
    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * @var String
     */
    protected $prefix;

    /**
     * @var \SplFileInfo[]
     */
    protected $files = array();

	/**
	 * @var String
	 */
	protected $tmpRunFolder;

	/**
	 * @var Bool
	 */
	protected $preserveRunFolder = false;

    public function __construct($prefix = '')
    {
        $this->prefix = $prefix;
        $this->filesystem = new Filesystem();
	    $this->tmpRunFolder = $this->getTmpPath();
    }

	public function initRunFolder()
	{
		clearstatcache();
		if (!file_exists($this->tmpRunFolder) && !is_dir($this->tmpRunFolder)) {
			$this->filesystem->mkdir($this->tmpRunFolder);
		}
	}

	/**
	 * @param bool $value
	 */
	public function setPreserveRunFolder($value)
	{
		$this->preserveRunFolder = $value;
	}

    /**
     * Get path to temp directory
     *
     * @return string
     */
    protected function getTmpPath()
    {
	    $tmpDir = sys_get_temp_dir();
	    if (!empty($this->prefix)) {
		    $tmpDir .= "/" . $this->prefix;
	    }
	    $tmpDir .= "/" . uniqid("run-", true);
        return $tmpDir;
    }

	/**
	 * Returns path to temp folder for current request
	 *
	 * @return string
	 */
	public function getTmpFolder()
	{
		return $this->tmpRunFolder;
	}

	/**
	 * Create empty file in TMP directory
	 *
	 * @param string $suffix filename suffix
	 * @param bool $preserve
	 * @throws \Exception
	 * @return \SplFileInfo
	 */
    public function createTmpFile($suffix = null, $preserve = false)
    {
	    $this->initRunFolder();

        $file = uniqid();

        if ($suffix) {
            $file .= '-' . $suffix;
        }

        $fileInfo = new \SplFileInfo($this->tmpRunFolder . '/' . $file);

        $this->filesystem->touch($fileInfo);
        $this->files[] = array(
	        'file'  => $fileInfo,
	        'preserve'  => $preserve
        );
        $this->filesystem->chmod($fileInfo, 0600);

        return $fileInfo;
    }

	/**
	 * Creates named temporary file
	 *
	 * @param $fileName
	 * @param bool $preserve
	 * @return \SplFileInfo
	 * @throws \Exception
	 */
	public function createFile($fileName, $preserve = false)
	{
		$this->initRunFolder();

		$fileInfo = new \SplFileInfo($this->tmpRunFolder . '/' . $fileName);

		$this->filesystem->touch($fileInfo);
		$this->files[] = array(
			'file'  => $fileInfo,
			'preserve'  => $preserve
		);
		$this->filesystem->chmod($fileInfo, 0600);

		return $fileInfo;
	}

    /**
     * Destructor
     *
     * Delete all files created by syrup component run
     */
    function __destruct()
    {
        $preserveRunFolder = $this->preserveRunFolder;

	    $fs = new Filesystem();

        foreach ($this->files as $file) {
	        if ($file['preserve']) {
		        $preserveRunFolder = true;
	        }
            if (file_exists($file['file']) && is_file($file['file']) && !$file['preserve']) {
                $fs->remove($file['file']);
            }
        }

	    if (!$preserveRunFolder) {
		    $fs->remove($this->tmpRunFolder);
	    }

    }
}
