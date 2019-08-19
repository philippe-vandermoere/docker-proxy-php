<?php
/**
 * @author Philippe VANDERMOERE <philippe@wizaplace.com>
 * @copyright Copyright (C) Philippe VANDERMOERE
 * @license MIT
 */

declare(strict_types=1);

namespace Test\App\Proxy;

use PHPUnit\Framework\TestCase;
use App\Proxy\Proxy;
use App\Proxy\ProxyCollection;
use steevanb\PhpTypedArray\Exception\KeyNotFoundException;
use steevanb\PhpTypedArray\ObjectArray\ObjectArray;
use Faker\Factory as FakerFactory;

class ProxyCollectionTest extends TestCase
{
    public function testConstruct(): void
    {
        $proxys = new ProxyCollection();
        static::assertEquals(0, $proxys->count());
        static::assertEquals(Proxy::class, $proxys->getClassName());
        static::assertInstanceOf(ObjectArray::class, $proxys);
    }

    public function testConstructWithProxys(): void
    {
        $faker = FakerFactory::create();
        $values = [];
        for ($i = 0; $i <= 10; $i++) {
            $values[] = new Proxy($i . $faker->domainName);
        }

        $proxys = new ProxyCollection($values);
        static::assertEquals(count($values), $proxys->count());
    }

    public function testOffsetGetValidKey(): void
    {
        $faker = FakerFactory::create();
        $proxys = new ProxyCollection();
        $proxys->offsetSet(
            $key = mt_rand(0, PHP_INT_MAX),
            $proxy = new Proxy($faker->domainName)
        );

        static::assertEquals($proxy, $proxys->offsetGet($proxy->getDomain()));
    }

    public function testOffsetGetInvalidKey(): void
    {
        $proxys = new ProxyCollection();
        static::expectException(KeyNotFoundException::class);
        $proxys->offsetGet(mt_rand(0, PHP_INT_MAX));
    }

    public function testCurrent(): void
    {
        $faker = FakerFactory::create();
        $proxy = new Proxy($faker->domainName);

        $proxys = new ProxyCollection([$proxy]);

        static::assertEquals($proxy, $proxys->current());
    }
}
