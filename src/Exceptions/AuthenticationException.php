<?php

namespace WMBH\Fibery\Exceptions;

class AuthenticationException extends FiberyException
{
    public function __construct(string $message = 'Invalid or missing API token')
    {
        parent::__construct($message, 401);
    }
}
