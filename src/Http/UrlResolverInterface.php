<?php

namespace Optimus\FineuploaderServer\Http;

use Optimus\FineuploaderServer\File\File;

interface UrlResolverInterface {

    public function __construct(array $config);

    public function resolve(File $file);

}
