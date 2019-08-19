<?php
/**
 * @author Philippe VANDERMOERE <philippe@wizaplace.com>
 * @copyright Copyright (C) Philippe VANDERMOERE
 * @license MIT
 */

declare(strict_types=1);

namespace Test\App\Certificate;

use PHPUnit\Framework\TestCase;
use App\Certificate\ProviderCollection;
use App\Certificate\ProviderInterface;
use steevanb\PhpTypedArray\Exception\KeyNotFoundException;
use steevanb\PhpTypedArray\ObjectArray\ObjectArray;

class ProviderCollectionTest extends TestCase
{
    public function testConstruct(): void
    {
        $providers = new ProviderCollection();
        static::assertEquals(0, $providers->count());
        static::assertEquals(ProviderInterface::class, $providers->getClassName());
        static::assertInstanceOf(ObjectArray::class, $providers);
    }

    public function testConstructWithProviders(): void
    {
        $values = [];
        for ($i = 0; $i <= 10; $i++) {
            $values[] = $this->createMock(ProviderInterface::class);
        }

        $providers = new ProviderCollection($values);
        static::assertEquals(count($values), $providers->count());
    }

    public function testOffsetGetValidKey(): void
    {
        $providers = new ProviderCollection();
        $providers->offsetSet(
            $key = mt_rand(0, PHP_INT_MAX),
            $provider = $this->createMock(ProviderInterface::class)
        );

        static::assertEquals($provider, $providers->offsetGet($key));
    }

    public function testOffsetGetInvalidKey(): void
    {
        $providers = new ProviderCollection();
        static::expectException(KeyNotFoundException::class);
        $providers->offsetGet(mt_rand(0, PHP_INT_MAX));
    }

    public function testCurrent(): void
    {
        $provider = $this->createMock(ProviderInterface::class);
        $providers = new ProviderCollection([$provider]);

        static::assertEquals($provider, $providers->current());
    }
}
