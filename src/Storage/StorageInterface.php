<?php

namespace Optimus\FineuploaderServer\Storage;

use Optimus\FineuploaderServer\File\File;

interface StorageInterface {

    public function store(File $file, $path);

    public function delete($filename);

}
