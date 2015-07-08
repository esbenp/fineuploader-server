<?php

namespace Optimus\FineuploaderServer\Response;

use Optimus\FineuploaderServer\File\File;

interface SuccessfulResponseInterface {

    public function __construct(File $file, array $fineUploaderResponse, $storageResponse);

    public function toArray();

}
