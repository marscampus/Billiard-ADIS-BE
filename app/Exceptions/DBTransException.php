<?php

namespace App\Exceptions;

use Exception;

class DBTransException extends Exception
{
    protected $code = 409;
}
