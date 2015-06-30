<?php

namespace Optimus\FineuploaderServer;

use Optimus\FineuploaderServer\Naming\NamingStrategyInterface;
use Optimus\FineuploaderServer\Storage\StorageInterface;
use Optimus\FineuploaderServer\Vendor\FineUploader;

class Uploader {

    private $storage;

    private $namingStrategy;

    private $config;

    public function __construct(
        StorageInterface $storage,
        NamingStrategyInterface $namingStrategy,
        array $config){
        $this->storage = $storage;
        $this->namingStrategy = $namingStrategy;
        $this->config = $config;

        $this->checkOrCreateTempDirectories();
    }

    public function upload($input){
        $fineUploader = $this->createFineUploaderInstance(
            $this->mergeFineUploaderConfig($input, $this->config['fine_uploader'])
        );

        $filePath = $fineUploader->getName();

        // Upload the file to the temporary directory so it can be forwarded
        // to the proper storage by the storage engine
        $file = new \SplFileInfo($filePath);
        $tempPath = $this->uploaderPath($this->config['temp_folder']);

        $upload = $fineUploader->handleUpload($tempPath, $file->getFilename());

        if (isset($upload['error'])) {
            return $upload;
        }

        // Get the full temporary path of the file, something like
        // storage/uploads/temp/{qquid}/file.ext
        $newTempPath = sprintf('%s/%s/%s', $tempPath, $upload['uuid'], $file->getFilename());
        $tempFile = new \SplFileInfo($newTempPath);

        // Get a new name based on the naming strategy
        $newName = $this->namingStrategy->generateName($tempFile);
        if ($newName !== $tempFile->getFilename()) {
            $renamedTempPath = sprintf('%s/%s', $tempFile->getPath(), $newName);
            rename($newTempPath, $renamedTempPath);
            $tempFile = new \SplFileInfo($renamedTempPath);
        }

        $storage = $this->storage->store($tempFile, $this->getStoragePath($input));

        return [
            'name' => $storage,
            'message' => 'Completed.',
            'type' => 'upload'
        ];
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

    public function setStorage(StorageInterface $storage)
    {
        $this->storage = $storage;
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

    private function checkOrCreateTempDirectories()
    {
        $chunksFolder = $this->uploaderPath($this->config['fine_uploader']['chunks_folder']);
        $tempFolder = $this->uploaderPath($this->config['temp_folder']);

        if (!is_dir($chunksFolder)) {
            mkdir($chunksFolder, 0755, true);
        }

        if (!is_dir($tempFolder)) {
            mkdir($tempFolder, 0755, true);
        }
    }

    private function uploaderPath($path)
    {
        return sprintf('%s%s', $this->config['uploader_folder'], $path);
    }

    private function getStoragePath(array $input)
    {
        return $input['sub_directory'] === 'null' || $input['sub_directory'] === '' ?
                    $input['base_directory'] :
                    sprintf('%s/%s', $input['base_directory'], $input['sub_directory']);
    }

}
