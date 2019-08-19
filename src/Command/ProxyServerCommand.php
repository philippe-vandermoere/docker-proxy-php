<?php
/**
 * @author Philippe VANDERMOERE <philippe@wizaplace.com>
 * @copyright Copyright (C) Philippe VANDERMOERE
 * @license MIT
 */

declare(strict_types=1);

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use React\EventLoop\Factory as EventLoopEventLoopFactory;

class ProxyServerCommand extends Command
{
    protected static $defaultName = 'proxy:start';

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $command = $this->getApplication()->find('proxy:run');
        $loop = EventLoopEventLoopFactory::create();
        $loop->addPeriodicTimer(
            5,
            function () use ($command, $input, $output) {
                try {
                    $command->run($input, $output);
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
