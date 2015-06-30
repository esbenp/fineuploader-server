<?php

namespace Optimus\FineuploaderServer\Storage;

use SplFileInfo;

interface StorageInterface {

    public function store(SplFileInfo $file, $path);

}
