<?php

namespace Optimus\Uploader;

use Optimus\Uploader\Storage\StorageInterface;
use Optimus\Uploader\Vendor\FineUploader;

class Uploader {

    private $storage;

    private $config;

    public function __construct(StorageInterface $storage, array $config){
        $this->storage = $storage;
        $this->config = $config;

        $this->checkOrCreateStorageDirectories();
    }

    public function upload($input){
        $fineUploader = $this->createFineUploaderInstance(
            $this->mergeFineUploaderConfig($input, $this->config['fine_uploader'])
        );

        $filePath = $fineUploader->getName();

        $file = new \SplFileInfo($filePath);
        $tempPath = $this->uploaderPath($this->config['temp_folder']);
        $newFilePath = sprintf('%s/%s', $tempPath, $file->getFilename());

        $upload = $fineUploader->handleUpload($tempPath, $file->getFilename());

        if (isset($upload['error'])) {
            return $upload;
        }

        $newTempPath = sprintf('%s/%s/%s', $tempPath, $upload['uuid'], $file->getFilename());

        $storage = $this->storage->store(new \SplFileInfo($newTempPath));
    }

    public function delete($filename){
        // 1. Get UUID from Client
        // 2. Create full path
        // 3. Delete using glob
        // 4. Return response (and UUID? <- why does it do this)
    }

    public function session($session){
        return $session === null ? [] : $session;
    }

    private function mergeFineUploaderConfig($input, array $config)
    {
        $allowedExtensions = $input['optimus_uploader_allowed_extensions'];
        if ($allowedExtensions !== '') {
            $config['allowed_extensions'] = explode(',', $allowedExtensions);
        }

        $sizeLimit = $input['optimus_uploader_size_limit'];
        if ($sizeLimit !== '0') {
            $config['size_limit'] = (int) $sizeLimit;
        }

        return $config;
    }

    private function createFineUploaderInstance(array $config)
    {
        $instance = new FineUploader();
        $instance->allowedExtensions = $config['allowed_extensions'];
        $instance->sizeLimit = $config['size_limit'];
        $instance->inputName = $config['input_name'];
        $instance->chunksFolder = $this->uploaderPath($config['chunks_folder']);

        return $instance;
    }

    private function checkOrCreateStorageDirectories()
    {
        $uploaderFolder = $this->config['uploader_folder'];

        if (!is_dir($uploaderFolder)) {
            mkdir($uploaderFolder, 0755);
        }

        $chunksFolder = $this->uploaderPath($this->config['fine_uploader']['chunks_folder']);
        $tempFolder = $this->uploaderPath($this->config['temp_folder']);

        if (!is_dir($chunksFolder)) {
            mkdir($chunksFolder, 0755);
        }

        if (!is_dir($tempFolder)) {
            mkdir($tempFolder, 0755);
        }
    }

    private function uploaderPath($path)
    {
        return sprintf('%s%s', $this->config['uploader_folder'], $path);
    }

}
