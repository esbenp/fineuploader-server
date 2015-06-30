<?php

namespace Optimus\Uploader\Storage;

use SplFileInfo;

interface StorageInterface {

    public function store(SplFileInfo $file);

}
