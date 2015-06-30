<?php

namespace Optimus\FineuploaderServer\Naming;

use SplFileInfo;
use Optimus\FineuploaderServer\Naming\NamingStrategyInterface;

class UniqidStrategy implements NamingStrategyInterface {

    public function generateName(SplFileInfo $file)
    {
        return sprintf('%s.%s', uniqid(), $file->getExtension());
    }

}
