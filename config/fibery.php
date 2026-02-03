<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Fibery Workspace
    |--------------------------------------------------------------------------
    |
    | Your Fibery workspace name. This is the subdomain part of your Fibery URL.
    | For example, if your URL is https://mycompany.fibery.io, enter "mycompany".
    |
    */
    'workspace' => env('FIBERY_WORKSPACE'),

    /*
    |--------------------------------------------------------------------------
    | API Token
    |--------------------------------------------------------------------------
    |
    | Your Fibery API token. You can generate one in Fibery:
    | Settings -> API Tokens -> Create Token
    |
    | Note: Each user can have up to 3 tokens.
    |
    */
    'token' => env('FIBERY_TOKEN'),

    /*
    |--------------------------------------------------------------------------
    | Request Timeout
    |--------------------------------------------------------------------------
    |
    | The maximum number of seconds to wait for a response from the Fibery API.
    |
    */
    'timeout' => env('FIBERY_TIMEOUT', 30),

    /*
    |--------------------------------------------------------------------------
    | Retry Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for automatic retries when rate limited (HTTP 429).
    |
    | Fibery limits:
    | - 3 requests per second per token
    | - 7 requests per second per workspace
    |
    */
    'retry' => [
        'times' => env('FIBERY_RETRY_TIMES', 3),
        'sleep' => env('FIBERY_RETRY_SLEEP', 1000), // milliseconds
    ],
];
