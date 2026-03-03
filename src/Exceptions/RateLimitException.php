<?php

namespace WMBH\Fibery\Exceptions;

class RateLimitException extends FiberyException
{
    protected int $retryAfter;

    public function __construct(string $message = 'Rate limit exceeded', int $retryAfter = 1, array $response = [])
    {
        parent::__construct($message, 429, null, $response);
        $this->retryAfter = $retryAfter;
    }

    public function getRetryAfter(): int
    {
        return $this->retryAfter;
    }
}
