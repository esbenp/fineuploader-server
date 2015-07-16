<?php

namespace Optimus\FineuploaderServer\Storage;

use Closure;
use Optimus\FineuploaderServer\Config\Config;
use Optimus\FineuploaderServer\File\Edition;
use Optimus\FineuploaderServer\File\File;
use Optimus\FineuploaderServer\File\RootFile;
use Optimus\FineuploaderServer\Storage\UrlResolverTrait;

class LocalStorage implements StorageInterface {

    use UrlResolverTrait;

    private $config;

    private $uploaderConfig;

    private $urlResolver;

    public function __construct(array $config, Config $uploaderConfig, $urlResolver)
    {
        $this->config = $config;
        $this->uploaderConfig = $uploaderConfig;
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
                $from_upload_path = sprintf('%s/%s', $edition->getUploaderPath(), $edition->getFilename());
                $to_upload_path = sprintf('%s/%s', $path, $edition->getFilename());
                $this->move($from_upload_path, $to_upload_path);
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

    public function get(RootFile $file)
    {
        if (!file_exists($file->getPathname())) {
            return [
                'error' => 'S0001' // session root file does not exist
            ];
        }

        if ($file->isImage()) {
            $thumbName = $file->generateEditionFilename("thumbnail");
            $thumbPath = sprintf('%s/%s', $file->getPath(), $thumbName);

            if (!file_exists($thumbPath)) {
                return [
                    'error' => 'S0002' // session thumbnail does not exist
                ];
            }

            $edition = new Edition("thumbnail", $thumbPath, $file->getUploaderPath(), [
                'type' => 'image'
            ], true);
            $edition->setUrl($this->resolveUrl($edition));

            $file->addEdition($edition);
        }

        return $file;
    }

    public function move($from, $to)
    {
        $from_path = sprintf('%s/%s', $this->config['root_folder'], $from);
        $to_path = sprintf('%s/%s', $this->config['root_folder'], $to);

        if (file_exists($from_path)) {
            rename($from_path, $to_path);

            return true;
        } else {
            return false;
        }
    }

    private function createStorageFolderIfNotExists()
    {
        $folder = $this->config['root_folder'];

        if (!is_dir($folder)) {
            mkdir($folder, 0755, true);
        }
    }

}
