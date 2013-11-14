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

class TempService
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

    public function __construct($prefix = '')
    {
        $this->prefix = $prefix;
        $this->filesystem = new Filesystem();
	    $this->tmpRunFolder = $this->getTmpPath();

        if (!file_exists($this->tmpRunFolder) && !is_dir($this->tmpRunFolder)) {
            $this->filesystem->mkdir($this->tmpRunFolder);
        }
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
	    $tmpDir .= "/" . uniqid("run-");
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
        $preserveRunFolder = false;

        foreach ($this->files as $file) {
            if (file_exists($file['file']) && is_file($file['file']) && !$file['preserve']) {
                unlink($file['file']);
            }
        }

	    if (!$preserveRunFolder) {
		    rmdir($this->tmpRunFolder);
	    }

    }
} 