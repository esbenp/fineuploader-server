<?php

namespace Optimus\FineuploaderServer\Naming;

use Optimus\FineuploaderServer\File\File;

interface NamingStrategyInterface {

    public function generateName(File $file);

}
