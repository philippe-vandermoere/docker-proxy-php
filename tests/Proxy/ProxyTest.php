<?php

/**
 * @author Philippe VANDERMOERE <philippe@wizaplace.com>
 * @copyright Copyright (C) Philippe VANDERMOERE
 * @license MIT
 */

declare(strict_types=1);

namespace Test\App\Proxy;

use App\Certificate\Certificate;
use App\Proxy\Server;
use App\Proxy\ServerCollection;
use PHPUnit\Framework\TestCase;
use App\Proxy\Proxy;
use Faker\Factory as FakerFactory;

class ProxyTest extends TestCase
{
    public function testConstruct(): void
    {
        $faker = FakerFactory::create();
        $proxy = new Proxy($domain = $faker->domainName);

        static::assertEquals($domain, $proxy->getDomain());
        static::assertEquals(null, $proxy->getCertificate());
        static::assertEquals([], $proxy->getPaths());
        static::assertEquals(new ServerCollection(), $proxy->getServers());
        static::assertEquals(false, $proxy->hasSsl());
    }

    public function testConstructWithCertificate(): void
    {
        $faker = FakerFactory::create();
        $proxy = new Proxy(
            $domain = $faker->domainName,
            $certificate = $this->createMock(Certificate::class)
        );

        static::assertEquals($domain, $proxy->getDomain());
        static::assertEquals($certificate, $proxy->getCertificate());
        static::assertEquals([], $proxy->getPaths());
        static::assertEquals(new ServerCollection(), $proxy->getServers());
        static::assertEquals(true, $proxy->hasSsl());
    }

    public function testConstructInvalidDomain(): void
    {
        $faker = FakerFactory::create();
        $domain = $faker->word;

        static::expectException(\InvalidArgumentException::class);
        static::expectExceptionMessage('domain `' . $domain . '` must be respect the RFC.');

        new Proxy($domain);
    }

    public function testServer(): void
    {
        $faker = FakerFactory::create();
        $proxy = new Proxy($faker->domainName);

        $paths = [];
        for ($i = 0; $i <= 5; $i++) {
            $path = '/' . $faker->slug;
            $paths[] = $path;
            $servers = new ServerCollection();
            for ($j = 0; $j <= 5; $j++) {
                $server = new Server($faker->uuid, $faker->word, $faker->ipv4, mt_rand(1, 65535));
                $servers[] = $server;
                static::assertEquals($proxy, $proxy->addServer($server, $path));
            }

            static::assertEquals($servers, $proxy->getServers($path));
        }

        static::assertEquals($paths, $proxy->getPaths());
    }
}
