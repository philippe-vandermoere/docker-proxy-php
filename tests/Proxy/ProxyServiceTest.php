<?php
/**
 * @author Philippe VANDERMOERE <philippe@wizaplace.com>
 * @copyright Copyright (C) Philippe VANDERMOERE
 * @license MIT
 */

declare(strict_types=1);

namespace Test\App\Proxy;

use App\Certificate\CertificateService;
use App\Proxy\NginxService;
use PhilippeVandermoere\DockerPhpSdk\DockerService;
use PHPUnit\Framework\TestCase;
use App\Proxy\ProxyService;
use Faker\Factory as FakerFactory;
use Test\App\Tools;

class ProxyServiceTest extends TestCase
{
    public function testConstruct(): void
    {
        $faker = FakerFactory::create();
        $proxyService = new ProxyService(
            $dockerService = $this->createMock(DockerService::class),
            $nginxService = $this->createMock(NginxService::class),
            $certificateService = $this->createMock(CertificateService::class),
            $dockerLabelDomain = $faker->word,
            $dockerLabelPort = $faker->word,
            $dockerLabelSsl = $faker->word,
            $dockerLabelPath = $faker->word,
            $dockerLabelCertificateProviderPrefix = $faker->word,
            $dockerLabelConnectDomain = $faker->word
        );

        static::assertEquals(
            80,
            Tools::getConstant($proxyService, 'DEFAULT_HTTP_PORT')
        );
        static::assertEquals(
            $dockerService,
            Tools::getPropertyValue($proxyService, 'dockerService')
        );

        static::assertEquals(
            $nginxService,
            Tools::getPropertyValue($proxyService, 'nginxService')
        );

        static::assertEquals(
            $certificateService,
            Tools::getPropertyValue($proxyService, 'certificateService')
        );

        static::assertEquals(
            $dockerLabelDomain,
            Tools::getPropertyValue($proxyService, 'dockerLabelDomain')
        );

        static::assertEquals(
            $dockerLabelPort,
            Tools::getPropertyValue($proxyService, 'dockerLabelPort')
        );

        static::assertEquals(
            $dockerLabelSsl,
            Tools::getPropertyValue($proxyService, 'dockerLabelSsl')
        );

        static::assertEquals(
            $dockerLabelPath,
            Tools::getPropertyValue($proxyService, 'dockerLabelPath')
        );

        static::assertEquals(
            $dockerLabelCertificateProviderPrefix,
            Tools::getPropertyValue($proxyService, 'dockerLabelCertificateProviderPrefix')
        );

        static::assertEquals(
            $dockerLabelConnectDomain,
            Tools::getPropertyValue($proxyService, 'dockerLabelConnectDomain')
        );
    }
}
