<?php

namespace Optimus\FineuploaderServer\Storage;

use Optimus\FineuploaderServer\File\File;
use Optimus\FineuploaderServer\File\RootFile;

interface StorageInterface {

    public function store(File $file, $path);

    public function delete($filename);

    public function get(RootFile $file);

    public function move($from, $to);

}
