<?php

namespace WMBH\Fibery\Exceptions;

use Exception;
use Throwable;

class FiberyException extends Exception
{
    protected array $response = [];

    public function __construct(string $message = '', int $code = 0, ?Throwable $previous = null, array $response = [])
    {
        parent::__construct($message, $code, $previous);
        $this->response = $response;
    }

    public function getResponse(): array
    {
        return $this->response;
    }

    public static function fromResponse(array $response, int $statusCode = 0): self
    {
        $message = $response['message'] ?? $response['error'] ?? 'Unknown Fibery API error';

        return new self($message, $statusCode, null, $response);
    }
}
