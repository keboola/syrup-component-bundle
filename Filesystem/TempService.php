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
     * @var bool
     */
    protected $preserve = false;

    public function __construct($prefix = '')
    {
        $this->prefix = $prefix;

        $this->filesystem = new Filesystem();

        if (!file_exists($this->getTmpPath()) && !is_dir($this->getTmpPath())) {
            $this->filesystem->mkdir($this->getTmpPath(), 0770);
        }
    }

    /**
     * If preserve is set to true, temporary files will not be deleted in destructor
     *
     */
    public function setPreserve($value)
    {
        $this->preserve = $value;
    }

    /**
     * Get path to temp directory
     *
     * @return string
     */
    public function getTmpPath()
    {
        return sys_get_temp_dir() . "/" . $this->prefix;
    }

    /**
     * Create empty file in TMP directory
     *
     * @param string $suffix filename suffix
     * @return \SplFileInfo
     * @throws IOException
     */
    public function createTmpFile($suffix = null)
    {
        $file = uniqid();

        if ($suffix) {
            $file .= '-' . $suffix;
        }

        $fileInfo = new \SplFileInfo($this->getTmpPath() . '/' . $file);

        try {
            $this->filesystem->touch($fileInfo);
            $this->files[] = $fileInfo;
            $this->filesystem->chmod($fileInfo, 0600);

            return $fileInfo;
        } catch (IOException $e) {
            throw $e;
        }
    }

    /**
     * Destructor
     *
     * Delete all files created by syrup component run
     */
    function __destruct()
    {
        if (!$this->preserve) {
            foreach ($this->files AS $fileInfo) {
                if (file_exists($fileInfo) && is_file($fileInfo)) {
                    unlink($fileInfo);
                }
            }
        }
    }
} 