<?php
/**
 * @author Philippe VANDERMOERE <philippe@wizaplace.com>
 * @copyright Copyright (C) Philippe VANDERMOERE
 * @license MIT
 */

declare(strict_types=1);

namespace Test\App\Proxy;

use App\Proxy\ProxyCollection;
use App\Proxy\ProxyService;
use PHPUnit\Framework\TestCase;
use App\Command\ProxyCommand;
use Test\App\Tools;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ProxyCommandTest extends TestCase
{
    public function testName(): void
    {
        static::assertEquals('proxy:run', ProxyCommand::getDefaultName());
    }

    public function testExecute(): void
    {
        $command = new ProxyCommand(
            $proxyService = $this->createMock(ProxyService::class)
        );

        $proxyService
            ->method('configureProxy')
            ->willReturn($proxyService)
        ;

        $proxyService
            ->method('reloadProxy')
            ->willReturn($proxyService)
        ;

        $proxyService
            ->method('getProxyCollection')
            ->willReturn($proxyCollection = new ProxyCollection())
        ;

        $proxyService
            ->expects($this->once())
            ->method('configureProxy')
            ->with($proxyCollection)
        ;

        $proxyService
            ->expects($this->once())
            ->method('reloadProxy')
        ;

        $proxyService
            ->expects($this->once())
            ->method('getProxyCollection')
        ;

        static::assertEquals(
            0,
            Tools::callProtectedMethod(
                $command,
                'execute',
                [
                    $this->createMock(InputInterface::class),
                    $this->createMock(OutputInterface::class)
                ]
            )
        );
    }
}
