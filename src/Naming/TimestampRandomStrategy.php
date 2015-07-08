<?php

namespace Optimus\FineuploaderServer\Naming;

use Optimus\FineuploaderServer\File\File;
use Optimus\FineuploaderServer\Naming\NamingStrategyInterface;

class TimestampRandomStrategy implements NamingStrategyInterface {

    public function generateName(File $file)
    {
        return sprintf('%s_%s.%s', time(), mt_rand(1,100000), $file->getExtension());
    }

}
