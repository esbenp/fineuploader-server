<?php

namespace Optimus\FineuploaderServer;

use Exception;
use Optimus\FineuploaderServer\File\RootFile;
use Optimus\FineuploaderServer\Naming\NamingStrategyInterface;
use Optimus\FineuploaderServer\Response\ErrorResponse;
use Optimus\FineuploaderServer\Response\SuccessfulResponseInterface;
use Optimus\FineuploaderServer\Storage\StorageInterface;
use Optimus\FineuploaderServer\Vendor\FineUploader;
use Optimus\Onion\Onion;

require_once __DIR__ . '/Vendor/utilities/delete_directory.php';

class Uploader {

    private $storage;

    private $namingStrategy;

    private $config;

    private $middleware;

    public function __construct(
        StorageInterface $storage,
        NamingStrategyInterface $namingStrategy,
        array $config,
        Onion $middleware){
        $this->storage = $storage;
        $this->namingStrategy = $namingStrategy;
        $this->config = $config;
        $this->middleware = $middleware;

        $this->checkOrCreateTempDirectories();
    }

    public function upload($input){
        $fineUploader = $this->createFineUploaderInstance(
            $this->mergeFineUploaderConfig($input, $this->config['fine_uploader'])
        );
//throw new \Exception;
        $filePath = $fineUploader->getName();

        // Upload the file to the temporary directory so it can be forwarded
        // to the proper storage by the storage engine
        $file = new RootFile($filePath);
        $tempPath = $this->uploaderPath($this->config['temp_folder']);

        $upload = $fineUploader->handleUpload($tempPath, $file->getFilename());

        if (isset($upload['error'])) {
            return (new ErrorResponse($upload['error']))->toArray();
        }

        // Get the full temporary path of the file, something like
        // storage/uploads/temp/{qquid}/file.ext
        $newTempPath = sprintf('%s/%s/%s', $tempPath, $upload['uuid'], $file->getFilename());
        $tempFile = new RootFile($newTempPath);

        // Get a new name based on the naming strategy
        $newName = $this->namingStrategy->generateName($tempFile);
        if ($newName !== $tempFile->getFilename()) {
            $renamedTempPath = sprintf('%s/%s', $tempFile->getPath(), $newName);
            rename($newTempPath, $renamedTempPath);
            $tempFile = new RootFile($renamedTempPath);
        }

        // This is the relative path from the uploader folder
        // i.e. /products/1
        $uploaderPath = $this->getStoragePath($input);
        $tempFile->setUploaderPath($uploaderPath);

        $core = function(RootFile $tempFile) use($uploaderPath) {
            return $this->storage->store($tempFile, $uploaderPath);
        };
        $storage = $this->middleware->peel($tempFile, $core);

        // TODO: Error handling

        // Remove temp directory
        optimus_delete_directory(sprintf('%s/%s', $tempPath, $upload['uuid']));

        $response = new $this->config['success_response_class']($tempFile, $upload, $storage);

        if (!($response instanceof SuccessfulResponseInterface)) {
            throw new Exception("Response class " . get_class($response) . " must implement " .
                                "Optimus\FineuploaderServer\Response\ResponseInterface");
        }

        return $this->prepareUploadSuccessfulResponse($response->toArray(), $tempFile);
    }

    private function prepareUploadSuccessfulResponse(array $response, RootFile $file)
    {
        return array_merge($response, [
            'type' => 'upload',
            'success' => true,
            'file_type' => $file->getType()
        ]);
    }

    public function delete($filename){
        return $this->storage->delete($filename);
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
