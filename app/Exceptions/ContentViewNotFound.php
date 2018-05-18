<?php

namespace App\Exceptions;

use Exception;
use Throwable;


class ContentViewNotFound extends Exception
{

    public function __construct($contentView, $versionId, Throwable $previous = null)
    {
        parent::__construct("'$contentView' content view not found for version id=$versionId", 0, $previous);
    }
}