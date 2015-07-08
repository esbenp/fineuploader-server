<?php

namespace Optimus\FineuploaderServer\Storage;

use Closure;
use Optimus\FineuploaderServer\File\File;
use Optimus\FineuploaderServer\Storage\UrlResolverTrait;

class LocalStorage implements StorageInterface {

    use UrlResolverTrait;

    private $config;

    private $urlResolver;

    public function __construct(array $config, $urlResolver)
    {
        $this->config = $config;
        $this->urlResolver = $urlResolver;

        $this->createStorageFolderIfNotExists();
    }

    public function store(File $file, $path)
    {
        $fullPath = sprintf('%s/%s', $this->config['root_folder'], $path);

        if (!is_dir($fullPath)) {
            mkdir($fullPath, 0755, true);
        }

        $filePath = sprintf('%s/%s', $fullPath, $file->getFilename());

        rename($file->getPathname(), $filePath);

        $file->setUrl($this->resolveUrl($file));
        $file->setName(sprintf('%s/%s', $file->getUploaderPath(), $file->getFilename()));

        foreach($file->getEditions() as $edition) {
            if ($edition->store() === true) {
                $newPath = sprintf('%s/%s', $fullPath, $edition->getFilename());
                rename($edition->getPathname(), $newPath);
                $edition->setUrl($this->resolveUrl($edition));
            }
        }

        return $file;
    }

    public function delete($filename)
    {
        $glob = substr_replace($filename, "*", strripos($filename, "."), 0);
        $path = sprintf('%s/%s', $this->config['root_folder'], $glob);

        foreach (glob($path) as $filename) {
            unlink($filename);
        }

        // TODO: Remove folder if empty
    }

    private function createStorageFolderIfNotExists()
    {
        $folder = $this->config['root_folder'];

        if (!is_dir($folder)) {
            mkdir($folder, 0755, true);
        }
    }

}
