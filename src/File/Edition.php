<?php

namespace Optimus\FineuploaderServer\File;

use Optimus\FineuploaderServer\File\File;

class Edition extends File {

    private $store;

    private $key;

    public function __construct($key, $file, $uploaderPath, $meta = [], $store = true)
    {
        parent::__construct($file);

        $this->key = $key;
        $this->store = $store;
        $this->meta = $meta;
        $this->uploaderPath = $uploaderPath;
    }

    public function store($store = null)
    {
        if ($store === null) {
            return $this->store;
        }

        $this->store = $store;
    }

    public function getKey()
    {
        return $this->key;
    }

}
