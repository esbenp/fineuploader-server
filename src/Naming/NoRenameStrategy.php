<?php

namespace Optimus\FineuploaderServer\Naming;

use SplFileInfo;
use Optimus\FineuploaderServer\Naming\NamingStrategyInterface;

class NoRenameStrategy implements NamingStrategyInterface {

    public function generateName(SplFileInfo $file)
    {
        return $file->getFilename();
    }

}
