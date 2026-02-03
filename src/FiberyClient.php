<?php

namespace WMBH\Fibery;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use WMBH\Fibery\Exceptions\AuthenticationException;
use WMBH\Fibery\Exceptions\FiberyException;
use WMBH\Fibery\Exceptions\RateLimitException;

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
                'args' => $args,
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
                    throw new FiberyException('Invalid JSON response from Fibery API');
                }

                // Check for errors in the response
                $this->checkResponseForErrors($data);

                return $data;
            } catch (ClientException $e) {
                $statusCode = $e->getResponse()->getStatusCode();
                $responseBody = $e->getResponse()->getBody()->getContents();

                /** @var array<mixed> $errorData */
                $errorData = json_decode($responseBody, true) ?? [];

                if ($statusCode === 429) {
                    $attempts++;
                    $lastException = new RateLimitException('Rate limit exceeded', 1);

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
