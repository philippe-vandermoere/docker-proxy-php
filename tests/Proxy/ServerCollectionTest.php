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
use App\Proxy\ServerCollection;
use steevanb\PhpTypedArray\Exception\KeyNotFoundException;
use steevanb\PhpTypedArray\ObjectArray\ObjectArray;
use Faker\Factory as FakerFactory;

class ServerCollectionTest extends TestCase
{
    public function testConstruct(): void
    {
        $servers = new ServerCollection();
        static::assertEquals(0, $servers->count());
        static::assertEquals(Server::class, $servers->getClassName());
        static::assertInstanceOf(ObjectArray::class, $servers);
    }

    public function testConstructWithServers(): void
    {
        $faker = FakerFactory::create();
        $values = [];
        for ($i = 0; $i <= 10; $i++) {
            $values[] = new Server(
                $faker->uuid,
                $faker->word,
                $faker->ipv4,
                mt_rand(1, 65535)
            );
        }

        $servers = new ServerCollection($values);
        static::assertEquals(count($values), $servers->count());
    }

    public function testOffsetGetValidKey(): void
    {
        $faker = FakerFactory::create();
        $servers = new ServerCollection();
        $servers->offsetSet(
            $key = mt_rand(0, PHP_INT_MAX),
            $server = new Server(
                $faker->uuid,
                $faker->word,
                $faker->ipv4,
                mt_rand(1, 65535)
            )
        );

        static::assertEquals($server, $servers->offsetGet($key));
    }

    public function testOffsetGetInvalidKey(): void
    {
        $servers = new ServerCollection();
        static::expectException(KeyNotFoundException::class);
        $servers->offsetGet(mt_rand(0, PHP_INT_MAX));
    }

    public function testCurrent(): void
    {
        $faker = FakerFactory::create();
        $server = new Server(
            $faker->uuid,
            $faker->word,
            $faker->ipv4,
            mt_rand(1, 65535)
        );

        $servers = new ServerCollection([$server]);

        static::assertEquals($server, $servers->current());
    }
}
