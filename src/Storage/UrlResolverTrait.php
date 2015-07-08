<?php

namespace Optimus\FineuploaderServer\Storage;

use Optimus\FineuploaderServer\File\File;

trait UrlResolverTrait {

    private function resolveUrl(File $edition)
    {
        $urlResolver = $this->urlResolver;
        return is_callable($urlResolver) ?
                        $urlResolver($edition) : $urlResolver->resolve($edition);

    }

}
