<?php

namespace App\Component;

/**
 * Handles the interaction with the progress resources which are readed
 * by the polling handler.
 *
 * Class ProgressPollerHandler
 * @package App\Components
 */
class ProgressPollerHandler
{

    /** @var string */
    private $path;

    /**
     * ProgressPollerHandler constructor.
     *
     * @param string $path
     * @param string $filename
     */
    public function __construct($path, $filename)
    {
        if (is_null($path) || empty($path)) {
            throw new \Exception('The path must not be null or empty');
        }

        if (is_null($filename) || empty($filename)) {
            throw new \Exception('The filename must not be null or empty');
        }

        $this->setPath($path.'/'.$filename);
    }

    /**
     * Writes the $content to the $file.
     *
     * @param   string  $file
     * @param   array   $content
     * @param   string  $accessType
     * @return  bool    Returns `true` if the content was write to the file,
     *                  `false` otherwise.
     */
    public function writeToPoll($content)
    {
        $path = $this->getPath();
        return file_put_contents($path, serialize(json_encode($content))) !== false;
    }

    /**
     * Writes the couple $key - $value to the resource file.
     *
     * @param   string $key
     * @param   string $value
     * @return  bool   Returns `true` if the process is succeed, `false` otherwise.
     */
    public function writeKeyToPoll($key, $value)
    {
        $file = file_get_contents($this->getPath());
        if ($file !== false) {
            $content       = json_decode(unserialize($file), true);
            $content[$key] = $value;
            return file_put_contents(
                $this->getPath(), serialize(json_encode($content))) !== false;
        } else {
            return false;
        }
    }

    /**
     * Reads and returns the content of the $file.
     *
     * @return array|mixed
     */
    public function readFromPoll()
    {
        $read = file_get_contents($this->getPath());
        if ($read) {
            $contentDecoded = json_decode(unserialize($read), true);
            return !is_null($contentDecoded) ? $contentDecoded : [];
        } else {
            return [];
        }
    }

    /**
     * Destroys the current resource.
     */
    public function destroy()
    {
        system('rm -f ' . escapeshellarg($this->getPath()));
    }

    /**
     * Returns if the resource
     *
     * @param  string $path
     * @return bool
     */
    public function resourceExists()
    {
        return file_exists($this->getPath());
    }

    /**
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * @param string $path
     */
    public function setPath($path)
    {
        $this->path = $path;
    }

}