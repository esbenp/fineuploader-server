<?php

namespace Optimus\FineuploaderServer\Response;

class ErrorResponse {

    private $error;

    public function __construct($error)
    {
        $this->error = $error;
    }

    public function toArray()
    {
        return [
            'error' => $this->error
        ];
    }

}
