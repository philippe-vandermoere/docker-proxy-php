<?php

/**
 * @author Philippe VANDERMOERE <philippe@wizaplace.com>
 * @copyright Copyright (C) Philippe VANDERMOERE
 * @license MIT
 */

declare(strict_types=1);

namespace App\Command;

use App\Proxy\ProxyService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use React\EventLoop\Factory as EventLoopEventLoopFactory;

class ProxyServerCommand extends Command
{
    protected static string $defaultName = 'proxy:start';
    protected ProxyService $proxyService;

    public function __construct(ProxyService $proxyService)
    {
        parent::__construct();

        $this->proxyService = $proxyService;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $proxyService = $this->proxyService;
        $loop = EventLoopEventLoopFactory::create();
        $loop->addPeriodicTimer(
            5,
            function () use ($proxyService, $output) {
                try {
                    $proxyService
                        ->configureProxy($this->proxyService->getProxyCollection())
                        ->reloadProxy()
                    ;
                } catch (\Throwable $throwable) {
                    $output->writeln(
                        sprintf(
                            "<error>In %s line %s: %s</error>\n%s",
                            $throwable->getFile(),
                            $throwable->getLine(),
                            $throwable->getMessage(),
                            $throwable->getTraceAsString()
                        )
                    );
                }
            }
        );

        $loop->run();

        return 0;
    }
}
