<?php

/**
 * @author Philippe VANDERMOERE <philippe@wizaplace.com>
 * @copyright Copyright (C) Philippe VANDERMOERE
 * @license MIT
 */

declare(strict_types=1);

namespace Test\App\Certificate\Provider;

use GuzzleHttp\Psr7\Request;
use Http\Client\HttpClient;
use PHPUnit\Framework\TestCase;
use App\Certificate\Provider\GithubClient;
use Faker\Factory as FakerFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Test\App\Tools;

class GithubClientTest extends TestCase
{
    public function testConstruct(): void
    {
        $githubClient = new GithubClient(
            $httpClient = $this->createMock(HttpClient::class)
        );

        static::assertEquals(
            $httpClient,
            Tools::getPropertyValue($githubClient, 'githubClient')
        );
        static::assertEquals(
            'https://api.github.com/repos',
            Tools::getConstant($githubClient, 'GITHUB_API_URL')
        );
    }

    public function testGetApiContent(): void
    {
        $faker = FakerFactory::create();
        $repository = $faker->word;
        $path = $faker->slug;
        $token = $faker->uuid;
        $reference = $faker->word;

        $githubClient = $this
            ->getMockBuilder(GithubClient::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['callApi'])
            ->getMock()
        ;

        $githubClient
            ->method('callApi')
            ->willReturn($response = $faker->text)
        ;

        $githubClient
            ->expects($this->once())
            ->method('callApi')
            ->with(
                '/' . $repository . '/contents/' . $path . '?ref=' . $reference,
                $token
            )
        ;

        static::assertEquals(
            $response,
            $githubClient->getApiContent($repository, $path, $token, $reference)
        );
    }

    /** @dataProvider getToken */
    public function testCallApi(?string $token, array $headers): void
    {
        $faker = FakerFactory::create();
        $httpClient = $this->createMock(HttpClient::class);
        $httpClient
            ->method('sendRequest')
            ->willReturn($response = $this->createMock(ResponseInterface::class))
        ;

        $githubClient = new GithubClient($httpClient);

        $response
            ->method('getStatusCode')
            ->willReturn(mt_rand(0, 399))
        ;

        $response
            ->method('getBody')
            ->willReturn($body = $this->createMock(StreamInterface::class))
        ;

        $body
            ->method('getContents')
            ->willReturn($responseText = $faker->text)
        ;

        $method = 'GET';
        $route = '/' . $faker->slug;

        $httpClient
            ->expects($this->once())
            ->method('sendRequest')
            ->with(
                new Request(
                    $method,
                    Tools::getConstant($githubClient, 'GITHUB_API_URL') . $route,
                    $headers
                )
            )
        ;

        $response
            ->expects($this->once())
            ->method('getStatusCode')
        ;

        $response
            ->expects($this->once())
            ->method('getBody')
        ;

        $body
            ->expects($this->once())
            ->method('getContents')
        ;

        static::assertEquals(
            $responseText,
            Tools::callProtectedMethod(
                $githubClient,
                'callApi',
                [$route, $token, $method]
            )
        );
    }

    /** @dataProvider getToken */
    public function testCallApiError(?string $token, array $headers): void
    {
        $faker = FakerFactory::create();
        $httpClient = $this->createMock(HttpClient::class);
        $httpClient
            ->method('sendRequest')
            ->willReturn($response = $this->createMock(ResponseInterface::class))
        ;

        $githubClient = new GithubClient($httpClient);

        $response
            ->method('getStatusCode')
            ->willReturn($httpCode = mt_rand(400, 599))
        ;

        $response
            ->method('getReasonPhrase')
            ->willReturn($error = $faker->text)
        ;

        $method = 'GET';
        $route = '/' . $faker->slug;

        $httpClient
            ->expects($this->once())
            ->method('sendRequest')
            ->with(
                new Request(
                    $method,
                    Tools::getConstant($githubClient, 'GITHUB_API_URL') . $route,
                    $headers
                )
            )
        ;

        $response
            ->expects($this->exactly(2))
            ->method('getStatusCode')
        ;

        $response
            ->expects($this->once())
            ->method('getReasonPhrase')
        ;

        static::expectException(\RuntimeException::class);
        static::expectExceptionMessage($error);
        static::expectExceptionCode($httpCode);
        Tools::callProtectedMethod(
            $githubClient,
            'callApi',
            [$route, $token, $method]
        );
    }

    public function getToken(): array
    {
        $faker = FakerFactory::create();
        $token = $faker->uuid;

        $headers = [
            'Accept' => 'application/vnd.github.v3.raw',
            'User-Agent' => 'docker-proxy'
        ];

        return [
            [null, $headers],
            ['token', array_merge($headers, ['Authorization' => 'token token'])],
            [$token, array_merge($headers, ['Authorization' => 'token ' . $token])],
        ];
    }
}
