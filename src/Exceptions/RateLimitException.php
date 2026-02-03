<?php

namespace WMBH\Fibery\Exceptions;

class RateLimitException extends FiberyException
{
    protected int $retryAfter;

    public function __construct(string $message = 'Rate limit exceeded', int $retryAfter = 1)
    {
        parent::__construct($message, 429);
        $this->retryAfter = $retryAfter;
    }

    public function getRetryAfter(): int
    {
        return $this->retryAfter;
    }
}
