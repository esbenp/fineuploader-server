<?php

namespace Optimus\FineuploaderServer;

use Exception;
use Optimus\FineuploaderServer\Config\Config;
use Optimus\FineuploaderServer\File\RootFile;
use Optimus\FineuploaderServer\Naming\NamingStrategyInterface;
use Optimus\FineuploaderServer\Response\ChunkResponse;
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
        Config $config,
        Onion $middleware){
        $this->storage = $storage;
        $this->namingStrategy = $namingStrategy;
        $this->config = $config;
        $this->middleware = $middleware;

        $this->checkOrCreateTempDirectories();
    }

    public function upload($input){
        $this->mergeConfig($input);

        $fineUploader = $this->createFineUploaderInstance(
            $this->mergeFineUploaderConfig($input, $this->config->get('fine_uploader'))
        );

        $filePath = $fineUploader->getName();

        // Upload the file to the temporary directory so it can be forwarded
        // to the proper storage by the storage engine
        $file = new RootFile($filePath);
        $tempPath = $this->uploaderPath($this->config->get('temp_folder'));

        $upload = $fineUploader->handleUpload($tempPath, $file->getFilename());

        if (isset($upload['error'])) {
            return (new ErrorResponse($upload['error']))->toArray();
        }

        // Chunked upload
        if (isset($input['qqpartindex'])) {
            if (($input['qqpartindex'] + 1) < $input['qqtotalparts']) {
                return (new ChunkResponse)->toArray();
            }
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

        $responseClass = $this->config->get('success_response_class');
        $response = new $responseClass($tempFile, $upload, $storage);

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
            'file_type' => $file->getType(),
            'upload_path' => $file->getName()
        ]);
    }

    public function delete($upload_path){
        $storage = $this->storage->delete($upload_path);

        return [
            'deleted' => [$upload_path]
        ];
    }

    public function session($input){
        $this->mergeConfig($input);

        $session = $input['optimus_uploader_files'];

        if ($session === null) {
            return [];
        }

        $return = [];
        foreach($session as $file) {
            $fileObj = new RootFile($this->uploaderPath('/'.$file));

            $uploaderPath = str_replace(
                '/'.$fileObj->getFilename(),
                '',
                $file
            );
            $fileObj->setUploaderPath($uploaderPath);

            $storage = $this->storage->get($fileObj);
            $id = \uniqid();

            if (is_array($storage) && isset($storage['error'])) {
                $return[] = array_merge([
                    'uuid' => $id,
                    'name' => $fileObj->getFilename(),
                    'upload_path' => $file,
                    'type' => 'error',
                    'error_code' => $storage['error']
                ], $storage);
            } else {
                $return[] = [
                    'name' => $storage->getFilename(),
                    'upload_path' => sprintf('%s/%s',
                                            $storage->getUploaderPath(),
                                            $storage->getFilename()
                                        ),
                    'file_type' => $storage->getType(),
                    'type' => 'session',
                    'thumbnailUrl' => $storage->getEdition('thumbnail')->getUrl(),
                    'uuid' => $id
                ];
            }
        }

        return $return;
    }

    public function setStorage(StorageInterface $storage)
    {
        $this->storage = $storage;
    }

    public function getStorage()
    {
        return $this->storage;
    }

    private function mergeConfig(array $input)
    {
        $height = $input['optimus_uploader_thumbnail_height'];
        $width = $input['optimus_uploader_thumbnail_width'];

        if (!empty($height)) {
            $this->config->set('thumbnails.height', $height);
        }

        if (!empty($width)) {
            $this->config->set('thumbnails.width', $width);
        }
    }

    private function mergeFineUploaderConfig($input, array $config)
    {
        $allowedExtensions = $input['optimus_uploader_allowed_extensions'];
        if (!empty($allowedExtensions)) {
            $config['allowed_extensions'] = explode(',', $allowedExtensions);
        }

        $sizeLimit = (int) $input['optimus_uploader_size_limit'];
        if (!empty($sizeLimit)) {
            $config['size_limit'] = $sizeLimit;
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
        $chunksFolder = $this->uploaderPath($this->config->get('fine_uploader.chunks_folder'));
        $tempFolder = $this->uploaderPath($this->config->get('temp_folder'));

        if (!is_dir($chunksFolder)) {
            mkdir($chunksFolder, 0755, true);
        }

        if (!is_dir($tempFolder)) {
            mkdir($tempFolder, 0755, true);
        }
    }

    private function uploaderPath($path)
    {
        return sprintf('%s%s', $this->config->get('uploader_folder'), $path);
    }

    private function getStoragePath(array $input)
    {
        return $input['sub_directory'] === 'null' || $input['sub_directory'] === '' ?
                    $input['base_directory'] :
                    sprintf('%s/%s', $input['base_directory'], $input['sub_directory']);
    }

}
