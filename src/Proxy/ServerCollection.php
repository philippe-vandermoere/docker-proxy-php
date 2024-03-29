<?php

/**
 * @author Philippe VANDERMOERE <philippe@wizaplace.com>
 * @copyright Copyright (C) Philippe VANDERMOERE
 * @license MIT
 */

declare(strict_types=1);

namespace App\Proxy;

use steevanb\PhpTypedArray\ObjectArray\ObjectArray;

class ServerCollection extends ObjectArray
{
    /** @param Server[] $values */
    public function __construct(iterable $values = [])
    {
        parent::__construct($values, Server::class);
    }

    /** @param mixed $offset */
    public function offsetGet($offset): Server
    {
        return parent::offsetGet($offset);
    }

    public function current(): Server
    {
        return parent::current();
    }
}
