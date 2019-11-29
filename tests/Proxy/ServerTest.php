<?php

/**
 * @author Philippe VANDERMOERE <philippe@wizaplace.com>
 * @copyright Copyright (C) Philippe VANDERMOERE
 * @license MIT
 */

declare(strict_types=1);

namespace Test\App\Proxy;

use PHPUnit\Framework\TestCase;
use App\Proxy\Server;
use Faker\Factory as FakerFactory;

class ServerTest extends TestCase
{
    public function testConstruct(): void
    {
        $faker = FakerFactory::create();
        $server = new Server(
            $id = $faker->uuid,
            $name = $faker->word,
            $ip = $faker->ipv4,
            $port = mt_rand(1, 65535)
        );

        static::assertEquals($id, $server->getId());
        static::assertEquals($name, $server->getName());
        static::assertEquals($ip, $server->getIp());
        static::assertEquals($port, $server->getPort());
    }

    public function testConstructInvalidIp(): void
    {
        $faker = FakerFactory::create();
        $ip = $faker->word;

        static::expectException(\InvalidArgumentException::class);
        static::expectExceptionMessage('ip `' . $ip . '` must respect the RFC');

        new Server(
            $faker->uuid,
            $faker->word,
            $ip,
            mt_rand(1, 65535)
        );
    }

    public function testConstructInvalidPort(): void
    {
        $faker = FakerFactory::create();
        if (1 === mt_rand(0, 1)) {
            $port = mt_rand(0, PHP_INT_MAX);
        } else {
            $port = mt_rand(-PHP_INT_MAX, 0);
        }

        static::expectException(\InvalidArgumentException::class);
        static::expectExceptionMessage('port `' . $port . '` must be between 1 and 65535.');

        new Server(
            $faker->uuid,
            $faker->word,
            $faker->ipv4,
            $port
        );
    }
}
