<?php

namespace Optimus\FineuploaderServer\Naming;

use Optimus\FineuploaderServer\File\File;
use Optimus\FineuploaderServer\Naming\NamingStrategyInterface;

class UniqidStrategy implements NamingStrategyInterface {

    public function generateName(File $file)
    {
        return sprintf('%s.%s', uniqid(), $file->getExtension());
    }

}
