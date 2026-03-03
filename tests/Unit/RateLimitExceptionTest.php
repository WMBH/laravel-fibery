<?php

use WMBH\Fibery\Exceptions\FiberyException;
use WMBH\Fibery\Exceptions\RateLimitException;

it('has default retry after value', function () {
    $exception = new RateLimitException;
    expect($exception->getRetryAfter())->toBe(1);
    expect($exception->getCode())->toBe(429);
});

it('accepts custom retry after from header', function () {
    $exception = new RateLimitException('Rate limit exceeded', 5);
    expect($exception->getRetryAfter())->toBe(5);
});

it('preserves response data', function () {
    $response = ['error' => 'rate limit', 'retry-after' => 3];
    $exception = new RateLimitException('Rate limit exceeded', 3, $response);
    expect($exception->getResponse())->toBe($response);
});

it('extends FiberyException', function () {
    $exception = new RateLimitException;
    expect($exception)->toBeInstanceOf(FiberyException::class);
});
