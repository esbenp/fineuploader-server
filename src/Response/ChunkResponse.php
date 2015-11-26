<?php

namespace Optimus\FineuploaderServer\Response;

use Optimus\FineuploaderServer\File\File;

class ChunkResponse {

    public function toArray()
    {
        $return = [
            'chunk' => true,
            'success' => true
        ];

        return $return;
    }

}
