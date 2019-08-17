<?php
/**
 * @author Philippe VANDERMOERE <philippe@wizaplace.com>
 * @copyright Copyright (C) Philippe VANDERMOERE
 * @license MIT
 */

declare(strict_types=1);

namespace Test\App\Proxy;

use PHPUnit\Framework\TestCase;
use App\Command\ProxyCommand;

class ProxyCommandTest extends TestCase
{
    public function testName(): void
    {
        static::assertEquals('proxy:run', ProxyCommand::getDefaultName());
    }
}
