<?php
/**
 * @author Philippe VANDERMOERE <philippe@wizaplace.com>
 * @copyright Copyright (C) Philippe VANDERMOERE
 * @license MIT
 */

declare(strict_types=1);

namespace App\Certificate\Provider;

use GuzzleHttp\Psr7\Request;
use Http\Client\HttpClient;

class GithubClient
{
    protected const GITHUB_API_URL = 'https://api.github.com/repos';

    /** @var HttpClient */
    protected $githubClient;

    public function __construct(HttpClient $githubClient)
    {
        $this->githubClient = $githubClient;
    }

    public function getApiContent(
        string $repository,
        string $path,
        string $token = null,
        string $reference = 'master'
    ): string {
        return $this->callApi(
            '/' . $repository . '/contents/' . $path . '?ref=' . $reference,
            $token
        );
    }

    protected function callApi(string $route, string $token = null, string $method = 'GET'): string
    {
        $headers = [
            'Accept' => 'application/vnd.github.v3.raw',
            'User-Agent' => 'docker-proxy',
        ];

        if (\is_string($token)) {
            $headers['Authorization'] = 'token ' . $token;
        }

        $response = $this->githubClient->sendRequest(
            new Request(
                $method,
                static::GITHUB_API_URL . $route,
                $headers
            )
        );

        if ($response->getStatusCode() >= 400) {
            throw new \RuntimeException($response->getReasonPhrase(), $response->getStatusCode());
        }

        return $response->getBody()->getContents();
    }
}
