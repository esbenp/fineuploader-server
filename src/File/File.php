<?php

namespace Optimus\FineuploaderServer\File;

use SplFileInfo;

abstract class File extends SplFileInfo {

    protected $uploaderPath;

    protected $url;

    protected $meta;

    public function getUploaderPath()
    {
        return $this->uploaderPath;
    }

    public function setUploaderPath($uploaderPath)
    {
        $this->uploaderPath = $uploaderPath;
    }

    public function getUrl()
    {
        return $this->url;
    }

    public function setUrl($url)
    {
        $this->url = $url;
    }

    public function setMeta($key, $value, $replace = true)
    {
        if ($this->hasMeta($key) && $replace === false) {
            return false;
        }

        $this->meta[$key] = $value;
    }

    public function hasMeta($key)
    {
        return array_key_exists($key, $this->meta);
    }

    public function getMeta($key = null)
    {
        if ($key !== null && !$this->hasMeta($key))
        {
            return false;
        }

        return $key === null ? $this->meta : $this->meta[$key];
    }

}
