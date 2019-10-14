<?php
/**
 * @author Philippe VANDERMOERE <philippe@wizaplace.com>
 * @copyright Copyright (C) Philippe VANDERMOERE
 * @license MIT
 */

declare(strict_types=1);

namespace Test\App\Certificate;

use PHPUnit\Framework\TestCase;
use App\Certificate\Certificate;
use Faker\Factory as FakerFactory;
use Test\App\Tools;

class CertificateTest extends TestCase
{
    public function testConstruct(): void
    {
        $faker = FakerFactory::create();
        $certificate = new Certificate(
            $domain = $faker->domainName,
            $certificateFilename = sys_get_temp_dir() . '/' . $faker->word,
            $privateKeyFilename = sys_get_temp_dir() . '/' . $faker->word,
            $certificateChainFilename = sys_get_temp_dir() . '/' . $faker->word
        );

        static::assertEquals($domain, $certificate->getDomain());
        static::assertEquals($certificateFilename, $certificate->getCertificateFilename());
        static::assertEquals($privateKeyFilename, $certificate->getPrivateKeyFilename());
        static::assertEquals($certificateChainFilename, $certificate->getCertificateChainFilename());
    }

    public function testConstructError(): void
    {
        $faker = FakerFactory::create();
        $domain = $faker->word;

        static::expectException(\InvalidArgumentException::class);
        static::expectExceptionMessage('domain `' . $domain . '` must be respect the RFC.');

        new Certificate(
            $domain,
            sys_get_temp_dir() . '/' . $faker->word,
            sys_get_temp_dir() . '/' . $faker->word,
            sys_get_temp_dir() . '/' . $faker->word
        );
    }

    public function testHasCertificateChain(): void
    {
        $faker = FakerFactory::create();
        $certificate = new Certificate(
            $faker->domainName,
            $certificateFilename = sys_get_temp_dir() . '/' . $faker->word,
            $privateKeyFilename = sys_get_temp_dir() . '/' . $faker->word,
            $certificateChainFilename = sys_get_temp_dir() . '/' . $faker->word
        );

        static::assertEquals(
            false,
            $certificate->hasCertificateChain()
        );

        file_put_contents($certificateChainFilename, $faker->text);

        static::assertEquals(
            true,
            $certificate->hasCertificateChain()
        );

        unlink($certificateChainFilename);
    }

    public function testWriteCertificate(): void
    {
        $faker = FakerFactory::create();
        $certificate = new Certificate(
            $faker->domainName,
            $certificateFilename = sys_get_temp_dir() . '/' . $faker->word,
            $privateKeyFilename = sys_get_temp_dir() . '/' . $faker->word,
            sys_get_temp_dir() . '/' . $faker->word
        );

        static::assertEquals(
            $certificate,
            $certificate->writeCertificate($certificateContent = $faker->text)
        );

        static::assertEquals(
            $certificateContent,
            file_get_contents($certificateFilename)
        );
        
        unlink($certificateFilename);
    }

    public function testWritePrivateKey(): void
    {
        $faker = FakerFactory::create();
        $certificate = new Certificate(
            $faker->domainName,
            $certificateFilename = sys_get_temp_dir() . '/' . $faker->word,
            $privateKeyFilename = sys_get_temp_dir() . '/' . $faker->word,
            $certificateChainFilename = sys_get_temp_dir() . '/' . $faker->word
        );

        static::assertEquals(
            $certificate,
            $certificate->writePrivateKey($privateKeyContent = $faker->text)
        );

        static::assertEquals(
            $privateKeyContent,
            file_get_contents($privateKeyFilename)
        );

        unlink($privateKeyFilename);
    }

    public function testWriteCertificateChain(): void
    {
        $faker = FakerFactory::create();
        $certificate = new Certificate(
            $faker->domainName,
            $certificateFilename = sys_get_temp_dir() . '/' . $faker->word,
            $privateKeyFilename = sys_get_temp_dir() . '/' . $faker->word,
            $certificateChainFilename = sys_get_temp_dir() . '/' . $faker->word
        );

        static::assertEquals(
            $certificate,
            $certificate->writeCertificateChain($certificateChainContent = $faker->text)
        );

        static::assertEquals(
            $certificateChainContent,
            file_get_contents($certificateChainFilename)
        );

        unlink($certificateChainFilename);
    }

    public function testStartDate(): void
    {
        $faker = FakerFactory::create();
        $date = $faker->dateTime;

        $certificate = $this
            ->getMockBuilder(Certificate::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['parseCertificate'])
            ->getMock()
        ;

        $certificate
            ->method('parseCertificate')
            ->willReturn(['validFrom' => $date->format('ymdHise')])
        ;

        $certificate
            ->expects($this->once())
            ->method('parseCertificate')
        ;

        static::assertEquals(
            $date,
            $certificate->getStartDate()
        );
    }

    public function testStartDateError(): void
    {
        $certificate = $this
            ->getMockBuilder(Certificate::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['parseCertificate'])
            ->getMock()
        ;

        $certificate
            ->method('parseCertificate')
            ->willReturn(['validFrom' => ''])
        ;

        $certificate
            ->expects($this->once())
            ->method('parseCertificate')
        ;

        static::expectException(\RuntimeException::class);
        static::expectExceptionMessage('Unable to parse certificate start date.');
        $certificate->getStartDate();
    }

    public function testExpireDate(): void
    {
        $faker = FakerFactory::create();
        $date = $faker->dateTime;

        $certificate = $this
            ->getMockBuilder(Certificate::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['parseCertificate'])
            ->getMock()
        ;

        $certificate
            ->method('parseCertificate')
            ->willReturn(['validTo' => $date->format('ymdHise')])
        ;

        $certificate
            ->expects($this->once())
            ->method('parseCertificate')
        ;

        static::assertEquals(
            $date,
            $certificate->getExpireDate()
        );
    }

    public function testExpireDateError(): void
    {
        $certificate = $this
            ->getMockBuilder(Certificate::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['parseCertificate'])
            ->getMock()
        ;

        $certificate
            ->method('parseCertificate')
            ->willReturn(['validTo' => ''])
        ;

        $certificate
            ->expects($this->once())
            ->method('parseCertificate')
        ;

        static::expectException(\RuntimeException::class);
        static::expectExceptionMessage('Unable to parse certificate expire date.');
        $certificate->getExpireDate();
    }

    /** @dataProvider getIsExpired */
    public function testIsExpired(
        bool $expected,
        \DateTimeInterface $expire,
        \DateTimeInterface $date = null
    ): void {
        $certificate = $this
            ->getMockBuilder(Certificate::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['parseCertificate'])
            ->getMock()
        ;

        $certificate
            ->method('parseCertificate')
            ->willReturn(['validTo' => $expire->format('ymdHise')])
        ;

        static::assertEquals(
            $expected,
            $certificate->isExpired($date)
        );
    }

    /** @dataProvider getIsValidCertificate */
    public function testIsValid(Certificate $certificate, bool $expected): void
    {
        static::assertEquals($expected, $certificate->isValid());
    }

    public function testParseCertificate(): void
    {
        $faker = FakerFactory::create();
        $certificate = new Certificate(
            $faker->domainName,
            $certificateFilename = __DIR__ . '/certificate.pem',
            $privateKeyFilename = sys_get_temp_dir() . '/' . $faker->word,
            sys_get_temp_dir() . '/' . $faker->word
        );

        $certificate = Tools::callProtectedMethod($certificate, 'parseCertificate');

        static::assertEquals('test.com', $certificate['subject']['CN']);
        static::assertEquals('190720114057Z', $certificate['validFrom']);
        static::assertEquals('200719114057Z', $certificate['validTo']);
        static::assertEquals(1563622857, $certificate['validFrom_time_t']);
        static::assertEquals(1595158857, $certificate['validTo_time_t']);
    }

    public function testParseInvalidCertificate(): void
    {
        $faker = FakerFactory::create();
        $certificate = new Certificate(
            $faker->domainName,
            $certificateFilename = __FILE__,
            $privateKeyFilename = sys_get_temp_dir() . '/' . $faker->word,
            sys_get_temp_dir() . '/' . $faker->word
        );

        static::expectException(\RuntimeException::class);
        static::expectExceptionMessage('Unable to parse certificate.');
        Tools::callProtectedMethod($certificate, 'parseCertificate');
    }

    public function getIsExpired(): array
    {
        return [
            [false, new \DateTime('tomorrow'), new \DateTime('now')],
            [false, new \DateTime('tomorrow'), null],
            [true, new \DateTime('now'), new \DateTime('now')],
            [true, new \DateTime('now'), null],
        ];
    }

    public function getIsValidCertificate(): array
    {
        $faker = FakerFactory::create();
        return [
            [
                new Certificate(
                    $faker->domainName,
                    sys_get_temp_dir() . '/' . $faker->word,
                    sys_get_temp_dir() . '/' . $faker->word,
                    sys_get_temp_dir() . '/' . $faker->word
                ),
                false
            ],
            [
                new Certificate(
                    $faker->domainName,
                    __DIR__ . '/certificate.pem',
                    __FILE__,
                    sys_get_temp_dir() . '/' . $faker->word
                ),
                false
            ],
            [
                new Certificate(
                    'test.com',
                    __DIR__ . '/certificate.pem',
                    __FILE__,
                    sys_get_temp_dir() . '/' . $faker->word
                ),
                false
            ],
            [
                new Certificate(
                    $faker->domainName,
                    __DIR__ . '/certificate.pem',
                    __DIR__ . '/privatekey.pem',
                    sys_get_temp_dir() . '/' . $faker->word
                ),
                false
            ],
            [
                new Certificate(
                    'test.com',
                    __DIR__ . '/certificate.pem',
                    __DIR__ . '/privatekey.pem',
                    sys_get_temp_dir() . '/' . $faker->word
                ),
                true
            ],
        ];
    }
}
