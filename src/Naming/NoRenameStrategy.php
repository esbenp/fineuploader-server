<?php

namespace Optimus\FineuploaderServer\Naming;

use Optimus\FineuploaderServer\File\File;
use Optimus\FineuploaderServer\Naming\NamingStrategyInterface;

class NoRenameStrategy implements NamingStrategyInterface {

    public function generateName(File $file)
    {
        return $file->getFilename();
    }

}
