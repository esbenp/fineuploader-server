<?php

namespace Optimus\FineuploaderServer\Naming;

use SplFileInfo;

interface NamingStrategyInterface {

    public function generateName(SplFileInfo $file); 

}
