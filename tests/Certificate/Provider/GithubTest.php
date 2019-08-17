<?php
/**
 * @author Philippe VANDERMOERE <philippe@wizaplace.com>
 * @copyright Copyright (C) Philippe VANDERMOERE
 * @license MIT
 */

declare(strict_types=1);

namespace Test\App\Certificate\Provider;

use App\Certificate\Certificate;
use App\Certificate\ProviderInterface;
use PHPUnit\Framework\TestCase;
use App\Certificate\Provider\Github;
use App\Certificate\Provider\GithubClient;
use Faker\Factory as FakerFactory;
use Test\App\Tools;

class GithubTest extends TestCase
{
    public function testConstruct(): void
    {
        $github = new Github(
            $httpClient = $this->createMock(GithubClient::class)
        );

        static::assertInstanceOf(ProviderInterface::class, $github);
        static::assertEquals(
            $httpClient,
            Tools::getPropertyValue($github, 'githubClient')
        );
    }

    public function testGetName(): void
    {
        $github = new Github($this->createMock(GithubClient::class));
        static::assertEquals('github', $github->getName());
    }

    public function testCreateCertificate(): void
    {
        $faker = FakerFactory::create();
        $githubClient = $this->createMock(GithubClient::class);
        $github = new Github($githubClient);
        $certificate = $this->createMock(Certificate::class);

        $options = [
            'token' => $token = $faker->uuid,
            'repository' => $repository = $faker->word,
            'certificate_path' => $certificatePath = $faker->word,
            'private_key_path' => $privateKeyPath = $faker->word,
        ];

        $githubClient
            ->method('getApiContent')
            ->willReturnOnConsecutiveCalls(
                $certificateContent = $faker->text,
                $privateKeyContent = $faker->text
            )
        ;

        $certificate
            ->method('getDomain')
            ->willReturn($domain = $faker->domainName)
        ;

        $certificate
            ->method('writeCertificate')
            ->willReturn($certificate)
        ;

        $certificate
            ->method('writePrivateKey')
            ->willReturn($certificate)
        ;

        $githubClient
            ->expects($this->exactly(2))
            ->method('getApiContent')
            ->withConsecutive(
                [
                    $repository,
                    $certificatePath,
                    $token,
                    'master'
                ],
                [
                    $repository,
                    $privateKeyPath,
                    $token,
                    'master'
                ]
            )
        ;

        $certificate
            ->expects($this->once())
            ->method('getDomain')
        ;

        $certificate
            ->expects($this->once())
            ->method('writeCertificate')
            ->with($certificateContent)
        ;

        $certificate
            ->expects($this->once())
            ->method('writePrivateKey')
            ->with($privateKeyContent)
        ;

        static::expectOutputString(
            'Certificate: Getting from github for domain `' . $domain . '`.' . PHP_EOL
        );

        static::assertEquals(
            $certificate,
            $github->createCertificate($certificate, $options)
        );
    }

    /** @dataProvider getCreateCertificateError */
    public function testCreateCertificateError(array $options, string $error): void
    {
        $githubClient = $this->createMock(GithubClient::class);
        $certificate = $this->createMock(Certificate::class);
        $github = new Github($githubClient);

        static::expectException(\InvalidArgumentException::class);
        static::expectExceptionMessage($error);
        $github->createCertificate($certificate, $options);
    }

    public function getCreateCertificateError(): array
    {
        $faker = FakerFactory::create();

        return [
            [
                [],
                'Missing parameter `token`.'
            ],
            [
                [
                    'token' => $faker->uuid,
                ],
                'Missing parameter `repository`.'
            ],
            [
                [
                    'token' => $faker->uuid,
                    'repository' => $faker->word,
                ],
                'Missing parameter `certificate_path`.'
            ],
            [
                [
                    'token' => $faker->uuid,
                    'repository' => $faker->word,
                    'certificate_path' => $faker->word,
                ],
                'Missing parameter `private_key_path`.'
            ],
        ];
    }
}
