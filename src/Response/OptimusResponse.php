<?php

namespace Optimus\FineuploaderServer\Response;

use Optimus\FineuploaderServer\File\File;
use Optimus\FineuploaderServer\Response\SuccessfulResponseInterface;

class OptimusResponse implements SuccessfulResponseInterface {

    private $file;

    private $storageResponse;

    private $fineUploaderResponse;

    public function __construct(File $file, array $fineUploaderResponse, $storageResponse)
    {
        $this->file = $file;
        $this->fineUploaderResponse = $fineUploaderResponse;
        $this->storageResponse = $storageResponse;
    }

    public function toArray()
    {
        $return = [];

        if ($this->file->hasEdition('thumbnail')) {
            $return['thumbnailUrl'] = $this->file->getEdition('thumbnail')->getUrl();
        }

        $return['name'] = $this->file->getFilename();

        return $return;
    }

}
