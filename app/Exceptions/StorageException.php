<?php

namespace App\Exceptions;

use Exception;
use Throwable;


class StorageException extends Exception
{

    public function __construct($url, $code = 0, Throwable $previous = null)
    {
        parent::__construct($url, $code, $previous);
    }

    public function getUrl() {
        return $this->getMessage();
    }
}