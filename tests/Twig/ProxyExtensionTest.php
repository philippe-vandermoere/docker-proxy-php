<?php
/**
 * @author Philippe VANDERMOERE <philippe@wizaplace.com>
 * @copyright Copyright (C) Philippe VANDERMOERE
 * @license MIT
 */

declare(strict_types=1);

namespace Test\App\Twig;

use App\Certificate\Certificate;
use App\Proxy\Proxy;
use App\Twig\ProxyExtension;
use PHPUnit\Framework\TestCase;
use Twig\TwigFilter;
use Faker\Factory as FakerFactory;

class ProxyExtensionTest extends TestCase
{
    public function testConstructInvalidHttpPort(): void
    {
        if (1 === mt_rand(0, 1)) {
            $httpPort = mt_rand(65536, PHP_INT_MAX);
        } else {
            $httpPort = mt_rand(-PHP_INT_MAX, 0);
        }

        static::expectException(\InvalidArgumentException::class);
        static::expectExceptionMessage('httpPort `' . $httpPort . '` must be between 1 and 65535.');
        new ProxyExtension($httpPort, 443);
    }

    public function testConstructInvalidHttpsPort(): void
    {
        if (1 === mt_rand(0, 1)) {
            $httpsPort = mt_rand(65536, PHP_INT_MAX);
        } else {
            $httpsPort = mt_rand(-PHP_INT_MAX, 0);
        }
        static::expectException(\InvalidArgumentException::class);
        static::expectExceptionMessage('httpsPort `' . $httpsPort . '` must be between 1 and 65535.');
        new ProxyExtension(80, $httpsPort);
    }

    public function testGetFilters(): void
    {
        $proxyExtension = new ProxyExtension(
            mt_rand(1, 65535),
            mt_rand(1, 65535)
        );

        static::assertEquals(
            [
                new TwigFilter('proxyHref', [$proxyExtension, 'proxyHref']),
                new TwigFilter('proxyUpstream', [$proxyExtension, 'proxyUpstream']),
            ],
            $proxyExtension->getFilters()
        );
    }

    /** @dataProvider getProxyHrefData */
    public function testProxyHref(string $domain, bool $ssl, string $path): void
    {
        $proxyExtension = new ProxyExtension(
            $httpPort = mt_rand(1, 65535),
            $httpsPort = mt_rand(1, 65535)
        );

        $proxy = new Proxy(
            $domain,
            $ssl ? $this->createMock(Certificate::class) : null
        );

        $expected = sprintf(
            '%s://%s:%d%s',
            $ssl ? 'https' : 'http',
            $domain,
            $ssl ? $httpsPort : $httpPort,
            $path
        );

        static::assertEquals(
            $expected,
            $proxyExtension->proxyHref($proxy, $path)
        );
    }

    public function testProxyUpstream(): void
    {
        $faker = FakerFactory::create();
        $proxyExtension = new ProxyExtension(
            mt_rand(1, 65535),
            mt_rand(1, 65535)
        );

        $domain = $faker->domainName;
        $expected = str_replace('.', '_', $domain);

        static::assertEquals(
            $expected,
            $proxyExtension->proxyUpstream(new Proxy($domain))
        );

        $path = '/' . $faker->slug . '/' . $faker->slug;
        $expected .= str_replace('/', '_', $path);

        static::assertEquals(
            $expected,
            $proxyExtension->proxyUpstream(new Proxy($domain), $path)
        );
    }

    public function getProxyHrefData(): array
    {
        $faker = FakerFactory::create();
        return [
            [$faker->domainName, false, '/'],
            [$faker->domainName, false, '/' . $faker->slug],
            [$faker->domainName, false, '/' . $faker->slug . '/' . $faker->slug],
            [$faker->domainName, true, '/'],
            [$faker->domainName, true, '/' . $faker->slug . '/' . $faker->slug],
        ];
    }
}
