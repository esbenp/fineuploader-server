<?php

namespace Optimus\FineuploaderServer\Storage;

use SplFileInfo;

class LocalStorage implements StorageInterface {

    private $config;

    public function __construct(array $config)
    {
        $this->config = $config;

        $this->createStorageFolderIfNotExists();
    }

    public function store(SplFileInfo $file, $path)
    {
        $path = sprintf('%s/%s', $this->config['root_folder'], $path);

        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }

        $filePath = sprintf('%s/%s', $path, $file->getFilename());

        rename($file->getPathname(), $filePath);
    }

    private function createStorageFolderIfNotExists()
    {
        $folder = $this->config['root_folder'];

        if (!is_dir($folder)) {
            mkdir($folder, 0755, true);
        }
    }

}
