<?php

namespace WMBH\Fibery\Exceptions;

class ConnectionException extends FiberyException
{
    public function __construct(string $message = 'Connection to Fibery failed', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
