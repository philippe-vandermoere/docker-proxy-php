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

class ProxyCommand extends Command
{
    protected static $defaultName = 'proxy:run';

    /** @var ProxyService */
    protected $proxyService;

    public function __construct(ProxyService $proxyService)
    {
        parent::__construct();

        $this->proxyService = $proxyService;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->proxyService
            ->configureProxy($this->proxyService->getProxyCollection())
            ->reloadProxy()
        ;

        return 0;
    }
}
