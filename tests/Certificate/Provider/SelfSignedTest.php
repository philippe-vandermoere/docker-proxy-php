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
use App\Certificate\Provider\SelfSigned;
use Test\App\Tools;
use Faker\Factory as FakerFactory;

class SelfSignedTest extends TestCase
{
    public function testConstruct(): void
    {
        $selfSigned = new SelfSigned();
        static::assertInstanceOf(ProviderInterface::class, $selfSigned);
        static::assertEquals(
            2048,
            Tools::getConstant($selfSigned, 'PRIVATE_KEY_LENGTH')
        );
        static::assertEquals(
            365,
            Tools::getConstant($selfSigned, 'CERTIFICATE_VALIDITY_DAYS')
        );
    }

    public function testGetName(): void
    {
        $selfSigned = new SelfSigned();
        static::assertEquals('self-signed', $selfSigned->getName());
    }

    public function testCreateCertificate(): void
    {
        $faker = FakerFactory::create();
        $selfSigned = new SelfSigned();
        $certificate = $this->createMock(Certificate::class);
        $domain = $faker->domainName;

        $certificate
            ->method('getDomain')
            ->willReturn($domain)
        ;

        $certificate
            ->method('writeCertificate')
            ->willReturn($certificate)
        ;

        $certificate
            ->method('writePrivateKey')
            ->willReturn($certificate)
        ;

        $certificate
            ->expects($this->exactly(2))
            ->method('getDomain')
        ;

        $certificate
            ->expects($this->once())
            ->method('writeCertificate')
            //->with()
        ;

        $certificate
            ->expects($this->once())
            ->method('writePrivateKey')
            //->with()
        ;


        static::expectOutputString(
            'Certificate: Created self signed for domain `' . $domain . '`' . PHP_EOL
        );
        static::assertEquals($certificate, $selfSigned->createCertificate($certificate));
    }
}
