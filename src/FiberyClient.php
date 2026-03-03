<?php

namespace WMBH\Fibery;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\GuzzleException;
use WMBH\Fibery\Exceptions\AuthenticationException;
use WMBH\Fibery\Exceptions\ConnectionException;
use WMBH\Fibery\Exceptions\FiberyException;
use WMBH\Fibery\Exceptions\RateLimitException;
use WMBH\Fibery\Exceptions\TimeoutException;

class FiberyClient
{
    protected Client $http;

    protected string $workspace;

    protected string $token;

    protected int $timeout;

    protected int $retryTimes;

    protected int $retrySleep;

    public function __construct(
        string $workspace,
        string $token,
        int $timeout = 30,
        int $retryTimes = 3,
        int $retrySleep = 1000
    ) {
        $this->workspace = $workspace;
        $this->token = $token;
        $this->timeout = $timeout;
        $this->retryTimes = $retryTimes;
        $this->retrySleep = $retrySleep;

        $this->http = new Client([
            'base_uri' => $this->getBaseUri(),
            'timeout' => $this->timeout,
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json; charset=utf-8',
                'Authorization' => 'Token '.$this->token,
            ],
        ]);
    }

    public function getBaseUri(): string
    {
        return "https://{$this->workspace}.fibery.io/";
    }

    public function getWorkspace(): string
    {
        return $this->workspace;
    }

    public function getToken(): string
    {
        return $this->token;
    }

    /**
     * Execute a single command against the Fibery API.
     *
     * @param  array<string, mixed>  $args
     * @return array<mixed>
     *
     * @throws FiberyException
     */
    public function command(string $command, array $args = []): array
    {
        $payload = [
            [
                'command' => $command,
                'args' => $args === [] ? new \stdClass : $args,
            ],
        ];

        $response = $this->request($payload);

        return $response[0] ?? [];
    }

    /**
     * Execute multiple commands in a batch.
     *
     * @param  array<array{command: string, args: array<string, mixed>}>  $commands
     * @return array<mixed>
     *
     * @throws FiberyException
     */
    public function batch(array $commands): array
    {
        return $this->request($commands);
    }

    /**
     * Execute commands using the internal batch command wrapper.
     *
     * @param  array<array{command: string, args: array<string, mixed>}>  $commands
     * @return array<mixed>
     *
     * @throws FiberyException
     */
    public function batchCommand(array $commands): array
    {
        return $this->command('fibery.command/batch', [
            'commands' => $commands,
        ]);
    }

    /**
     * Execute a raw HTTP request against the Fibery API.
     *
     * @param  array<string, mixed>  $options  Guzzle request options
     * @return array<mixed>
     *
     * @throws FiberyException
     */
    public function rawRequest(string $method, string $uri, array $options = []): array
    {
        $body = $this->executeRawRequest($method, $uri, $options);

        /** @var array<mixed> $data */
        $data = json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new FiberyException('Invalid JSON response from Fibery API: '.json_last_error_msg());
        }

        return $data;
    }

    /**
     * Execute a raw HTTP request and return the response body as a string.
     *
     * @param  array<string, mixed>  $options  Guzzle request options
     *
     * @throws FiberyException
     */
    public function rawDownload(string $method, string $uri, array $options = []): string
    {
        return $this->executeRawRequest($method, $uri, $options);
    }

    /**
     * Execute a raw HTTP request with retry logic.
     *
     * @param  array<string, mixed>  $options
     *
     * @throws FiberyException
     */
    protected function executeRawRequest(string $method, string $uri, array $options = []): string
    {
        // Ensure auth header is included
        $options['headers'] = array_merge([
            'Authorization' => 'Token '.$this->token,
            'Accept' => 'application/json',
        ], $options['headers'] ?? []);

        $attempts = 0;
        $lastException = null;

        while ($attempts < $this->retryTimes) {
            try {
                $response = $this->http->request($method, $uri, $options);

                return $response->getBody()->getContents();
            } catch (ConnectException $e) {
                throw $this->classifyConnectException($e);
            } catch (ClientException $e) {
                $statusCode = $e->getResponse()->getStatusCode();

                if ($statusCode === 429) {
                    $attempts++;
                    $retryAfter = (int) ($e->getResponse()->getHeaderLine('Retry-After') ?: 1);
                    $lastException = new RateLimitException('Rate limit exceeded', $retryAfter);

                    if ($attempts < $this->retryTimes) {
                        usleep($this->retrySleep * 1000);

                        continue;
                    }

                    throw $lastException;
                }

                if ($statusCode === 401) {
                    throw new AuthenticationException('Invalid or missing API token');
                }

                $responseBody = $e->getResponse()->getBody()->getContents();
                /** @var array<mixed> $errorData */
                $errorData = json_decode($responseBody, true) ?? [];

                throw FiberyException::fromResponse($errorData, $statusCode);
            } catch (GuzzleException $e) {
                throw new FiberyException('HTTP request failed: '.$e->getMessage(), 0, $e);
            }
        }

        throw $lastException ?? new FiberyException('Max retry attempts exceeded');
    }

    /**
     * Send a request to the Fibery API with retry logic for rate limits.
     *
     * @param  array<mixed>  $payload
     * @return array<mixed>
     *
     * @throws FiberyException
     */
    protected function request(array $payload): array
    {
        $attempts = 0;
        $lastException = null;

        while ($attempts < $this->retryTimes) {
            try {
                $response = $this->http->post('api/commands', [
                    'json' => $payload,
                ]);

                $body = $response->getBody()->getContents();

                /** @var array<mixed> $data */
                $data = json_decode($body, true);

                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new FiberyException('Invalid JSON response from Fibery API: '.json_last_error_msg());
                }

                // Check for errors in the response
                $this->checkResponseForErrors($data);

                return $data;
            } catch (ConnectException $e) {
                throw $this->classifyConnectException($e);
            } catch (ClientException $e) {
                $statusCode = $e->getResponse()->getStatusCode();
                $responseBody = $e->getResponse()->getBody()->getContents();

                /** @var array<mixed> $errorData */
                $errorData = json_decode($responseBody, true) ?? [];

                if ($statusCode === 429) {
                    $attempts++;
                    $retryAfter = (int) ($e->getResponse()->getHeaderLine('Retry-After') ?: 1);
                    $lastException = new RateLimitException('Rate limit exceeded', $retryAfter);

                    if ($attempts < $this->retryTimes) {
                        usleep($this->retrySleep * 1000);

                        continue;
                    }

                    throw $lastException;
                }

                if ($statusCode === 401) {
                    throw new AuthenticationException('Invalid or missing API token');
                }

                throw FiberyException::fromResponse($errorData, $statusCode);
            } catch (GuzzleException $e) {
                throw new FiberyException('HTTP request failed: '.$e->getMessage(), 0, $e);
            }
        }

        throw $lastException ?? new FiberyException('Max retry attempts exceeded');
    }

    /**
     * Check the API response for errors.
     *
     * @param  array<mixed>  $data
     *
     * @throws FiberyException
     */
    protected function checkResponseForErrors(array $data): void
    {
        foreach ($data as $result) {
            if (is_array($result) && isset($result['result']) && is_array($result['result'])) {
                $resultData = $result['result'];
                if (isset($resultData['error'])) {
                    throw FiberyException::fromResponse($resultData);
                }
            }
        }
    }

    /**
     * Classify a ConnectException as either TimeoutException or ConnectionException.
     */
    protected function classifyConnectException(ConnectException $e): ConnectionException
    {
        $message = $e->getMessage();

        if (str_contains($message, 'timed out') || str_contains($message, 'timeout')) {
            return new TimeoutException('Request to Fibery timed out: '.$message, 0, $e);
        }

        return new ConnectionException('Connection to Fibery failed: '.$message, 0, $e);
    }

    /**
     * Get the schema of the workspace.
     *
     * @return array<mixed>
     *
     * @throws FiberyException
     */
    public function getSchema(): array
    {
        return $this->command('fibery.schema/query');
    }
}
