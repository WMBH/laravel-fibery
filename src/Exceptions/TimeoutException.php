<?php

namespace WMBH\Fibery\Exceptions;

class TimeoutException extends ConnectionException
{
    public function __construct(string $message = 'Request to Fibery timed out', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
