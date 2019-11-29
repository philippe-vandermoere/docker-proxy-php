<?php

/**
 * @author Philippe VANDERMOERE <philippe@wizaplace.com>
 * @copyright Copyright (C) Philippe VANDERMOERE
 * @license MIT
 */

declare(strict_types=1);

namespace App\Proxy;

use steevanb\PhpTypedArray\ObjectArray\ObjectArray;

class ProxyCollection extends ObjectArray
{
    public function __construct(iterable $values = [])
    {
        parent::__construct($values, Proxy::class);
    }

    public function offsetSet($offset, $value): void
    {
        /** @var Proxy $value */
        parent::offsetSet($value->getDomain(), $value);
    }

    public function offsetGet($offset): Proxy
    {
        return parent::offsetGet($offset);
    }

    public function current(): Proxy
    {
        return parent::current();
    }
}
