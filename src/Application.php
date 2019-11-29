<?php

/**
 * @author Philippe VANDERMOERE <philippe@wizaplace.com>
 * @copyright Copyright (C) Philippe VANDERMOERE
 * @license MIT
 */
 
declare(strict_types=1);

namespace App;

use Symfony\Component\Console\Application as ConsoleApplication;
use Symfony\Component\Console\Command\Command;

class Application extends ConsoleApplication
{
    /** @param Command[] $commands */
    public function __construct(iterable $commands)
    {
        parent::__construct('console', '1.0.0');
        foreach ($commands as $command) {
            if ($command instanceof Command) {
                $this->add($command);
            }
        }
    }
}
