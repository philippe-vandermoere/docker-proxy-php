<?php
/**
 * @author Philippe VANDERMOERE <philippe@wizaplace.com>
 * @copyright Copyright (C) Philippe VANDERMOERE
 * @license MIT
 */

declare(strict_types=1);

namespace Test\App\Certificate;

use App\Certificate\Certificate;
use App\Certificate\Provider\SelfSigned;
use App\Certificate\ProviderCollection;
use App\Certificate\ProviderInterface;
use PHPUnit\Framework\TestCase;
use App\Certificate\CertificateService;
use Faker\Factory as FakerFactory;

class CertificateServiceTest extends TestCase
{
    /** @dataProvider getDataDirectory */
    public function testConstruct(string $directory, string $directoryExpected): void
    {
        $certificateService = new CertificateService([], $directory);
        static::assertEquals(
            $directoryExpected,
            $this->getPropertyValue($certificateService, 'certificateDirectory')
        );
    }

    public function testConstructWithProviders(): void
    {
        $providers = [];
        for ($i = 0; $i <= 10, $i++;) {
            $providers[] = $this->createMock(ProviderInterface::class);
        }

        $certificateService = new CertificateService($providers, sys_get_temp_dir());
        static::assertEquals(
            new ProviderCollection($providers),
            $this->getPropertyValue($certificateService, 'providers')
        );
    }

    public function testConstructInvalidDirectory(): void
    {
        $directory = __FILE__;
        static::expectException(\InvalidArgumentException::class);
        static::expectExceptionMessage($directory . ' is not a directory.');
        new CertificateService([], $directory);
    }

    public function testGetCertificate(): void
    {
        $faker = FakerFactory::create();
        $certificateService = $this
            ->getMockBuilder(CertificateService::class)
            ->disableOriginalConstructor()
            ->setMethods(['createEmptyCertificate', 'getProvider'])
            ->getMock()
        ;

        $domain = $faker->domainName;
        $certificate = $this->createMock(Certificate::class);
        $provider = $this->createMock(ProviderInterface::class);

        $options = [
            'name' => $faker->word,
            $faker->word => $faker->word,
            $faker->word => $faker->word,
            $faker->word => $faker->word,
            $faker->word => $faker->word,
            $faker->word => $faker->word,
            $faker->word => $faker->word,
        ];

        $certificateService
            ->method('createEmptyCertificate')
            ->willReturn($certificate);
        ;

        $certificateService
            ->method('getProvider')
            ->willReturn($provider);
        ;

        $certificate
            ->method('isValid')
            ->willReturnOnConsecutiveCalls(false, true);
        ;

        $provider
            ->method('createCertificate')
            ->willReturn($certificate);
        ;

        $certificateService
            ->expects($this->once())
            ->method('createEmptyCertificate')
            ->with($domain);
        ;

        $certificateService
            ->expects($this->once())
            ->method('getProvider')
            ->with($options['name'])
        ;

        $certificate
            ->expects($this->exactly(2))
            ->method('isValid')
        ;

        $certificate
            ->expects($this->never())
            ->method('isExpired')
        ;

        $provider
            ->expects($this->once())
            ->method('createCertificate')
            ->with($certificate, $options);
        ;

        static::assertEquals($certificate, $certificateService->getCertificate($domain, $options));
    }

    public function testGetCertificateValid(): void
    {
        $faker = FakerFactory::create();
        $certificateService = $this
            ->getMockBuilder(CertificateService::class)
            ->disableOriginalConstructor()
            ->setMethods(['createEmptyCertificate', 'getProvider'])
            ->getMock()
        ;

        $domain = $faker->domainName;
        $certificate = $this->createMock(Certificate::class);

        $certificateService
            ->method('createEmptyCertificate')
            ->willReturn($certificate);
        ;

        $certificate
            ->method('isValid')
            ->willReturn(true);
        ;

        $certificate
            ->method('isExpired')
            ->willReturn(false);
        ;

        $certificateService
            ->expects($this->once())
            ->method('createEmptyCertificate')
            ->with($domain);
        ;

        $certificateService
            ->expects($this->never())
            ->method('getProvider')
        ;

        $certificate
            ->expects($this->once())
            ->method('isValid')
        ;

        $certificate
            ->expects($this->once())
            ->method('isExpired')
        ;

        static::assertEquals($certificate, $certificateService->getCertificate($domain, []));
        static::expectOutputString(
            'Certificate: The certificate is valid for domain ' . $domain . '.' . PHP_EOL
        );
    }

    public function testGetCertificateError(): void
    {
        $faker = FakerFactory::create();
        $certificateService = $this
            ->getMockBuilder(CertificateService::class)
            ->disableOriginalConstructor()
            ->setMethods(['createEmptyCertificate', 'getProvider'])
            ->getMock()
        ;

        $domain = $faker->domainName;
        $certificate = $this->createMock(Certificate::class);
        $provider = $this->createMock(ProviderInterface::class);

        $options = [
            'name' => $faker->word,
            $faker->word => $faker->word,
            $faker->word => $faker->word,
            $faker->word => $faker->word,
            $faker->word => $faker->word,
            $faker->word => $faker->word,
            $faker->word => $faker->word,
        ];

        $certificateService
            ->method('createEmptyCertificate')
            ->willReturn($certificate);
        ;

        $certificateService
            ->method('getProvider')
            ->willReturn($provider);
        ;

        $certificate
            ->method('isValid')
            ->willReturn(false);
        ;

        $provider
            ->method('createCertificate')
            ->willReturn($certificate);
        ;

        $certificateService
            ->expects($this->once())
            ->method('createEmptyCertificate')
            ->with($domain);
        ;

        $certificateService
            ->expects($this->once())
            ->method('getProvider')
            ->with($options['name'])
        ;

        $certificate
            ->expects($this->exactly(2))
            ->method('isValid')
        ;

        $certificate
            ->expects($this->never())
            ->method('isExpired')
        ;

        $provider
            ->expects($this->once())
            ->method('createCertificate')
            ->with($certificate, $options);
        ;

        static::expectException(\RuntimeException::class);
        static::expectExceptionMessage('Unable to create certificate for domain `' . $domain . '`.');
        $certificateService->getCertificate($domain, $options);
    }

    public function testCreateEmptyCertificate(): void
    {
        $faker = FakerFactory::create();
        $certificateService = new CertificateService([], $directory = sys_get_temp_dir());

        $certificate = $this->callProtectedMethod(
            $certificateService,
            'createEmptyCertificate',
            [ $domain = $faker->domainName]
        );

        static::assertEquals(true, \is_dir($directory . '/' . $domain));
        rmdir($directory . '/' . $domain);

        static::assertInstanceOf(Certificate::class, $certificate);
        static::assertEquals($domain, $certificate->getDomain());
        static::assertEquals(
            $directory . '/' . $domain . '/certificate.pem',
            $certificate->getCertificateFilename()
        );
        static::assertEquals(
            $directory . '/' . $domain . '/privatekey.pem',
            $certificate->getPrivateKeyFilename()
        );
    }

    public function testGetProvider(): void
    {
        $faker = FakerFactory::create();
        $provider = $this->createMock(ProviderInterface::class);

        $provider
            ->method('getName')
            ->willReturn($providerName = $faker->word)
        ;

        $provider
            ->expects($this->once())
            ->method('getName')
        ;

        $certificateService = new CertificateService([$provider], sys_get_temp_dir());
        static::assertEquals(
            $provider,
            $this->callProtectedMethod(
                $certificateService,
                'getProvider',
                [$providerName]
            )
        );
    }

    public function testGetProviderFallback(): void
    {
        $faker = FakerFactory::create();
        $provider = $this->createMock(ProviderInterface::class);

        $provider
            ->method('getName')
            ->willReturn('')
        ;

        $provider
            ->expects($this->once())
            ->method('getName')
        ;

        $certificateService = new CertificateService([$provider], sys_get_temp_dir());
        static::assertInstanceOf(
            SelfSigned::class,
            $this->callProtectedMethod(
                $certificateService,
                'getProvider',
                [$faker->word]
            )
        );
    }

    public function getDataDirectory(): array
    {
        return [
            [sys_get_temp_dir(), sys_get_temp_dir()],
            [sys_get_temp_dir() . '/', sys_get_temp_dir()],
            [__DIR__, __DIR__],
            [__DIR__ . '/', __DIR__],
        ];
    }

    protected function getPropertyValue(CertificateService $certificateService, string $property)
    {
        $reflectionClass = new \ReflectionClass($certificateService);
        $reflectionProperty = $reflectionClass->getProperty($property);
        $reflectionProperty->setAccessible(true);

        return $reflectionProperty->getValue($certificateService);
    }

    protected function callProtectedMethod(CertificateService $certificateService, string $method, array $args = [])
    {
        $reflectionClass = new \ReflectionClass($certificateService);
        $reflectionMethod = $reflectionClass->getMethod($method);
        $reflectionMethod->setAccessible(true);

        return $reflectionMethod->invokeArgs($certificateService, $args);
    }
}
