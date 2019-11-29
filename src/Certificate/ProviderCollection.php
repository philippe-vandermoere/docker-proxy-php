<?php

/**
 * @author Philippe VANDERMOERE <philippe@wizaplace.com>
 * @copyright Copyright (C) Philippe VANDERMOERE
 * @license MIT
 */

declare(strict_types=1);

namespace App\Certificate;

use steevanb\PhpTypedArray\ObjectArray\ObjectArray;

class ProviderCollection extends ObjectArray
{
    /** @param ProviderInterface[] $values */
    public function __construct(iterable $values = [])
    {
        parent::__construct($values, ProviderInterface::class);
    }

    /** @param mixed $offset */
    public function offsetGet($offset): ProviderInterface
    {
        return parent::offsetGet($offset);
    }

    public function current(): ProviderInterface
    {
        return parent::current();
    }
}
