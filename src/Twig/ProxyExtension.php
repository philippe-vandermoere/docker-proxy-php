<?php

/**
 * @author Philippe VANDERMOERE <philippe@wizaplace.com>
 * @copyright Copyright (C) Philippe VANDERMOERE
 * @license MIT
 */

declare(strict_types=1);

namespace App\Twig;

use App\Proxy\Proxy;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use App\Validator\Validator;

class ProxyExtension extends AbstractExtension
{
    protected int $httpPort;
    protected int $httpsPort;

    public function __construct(int $httpPort, int $httpsPort)
    {
        if (false === Validator::validatePort($httpPort)) {
            throw new \InvalidArgumentException('httpPort `' . $httpPort . '` must be between 1 and 65535.');
        }

        if (false === Validator::validatePort($httpsPort)) {
            throw new \InvalidArgumentException('httpsPort `' . $httpsPort . '` must be between 1 and 65535.');
        }

        $this->httpPort = $httpPort;
        $this->httpsPort = $httpsPort;
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('proxyHref', [$this, 'proxyHref']),
            new TwigFilter('proxyUpstream', [$this, 'proxyUpstream']),
        ];
    }

    public function proxyHref(Proxy $proxy, string $path = '/'): string
    {
        return sprintf(
            '%s://%s:%d%s',
            (true === $proxy->hasSsl()) ? 'https' : 'http',
            $proxy->getDomain(),
            (true === $proxy->hasSsl()) ? $this->httpsPort : $this->httpPort,
            $path
        );
    }

    public function proxyUpstream(Proxy $proxy, string $path = '/'): string
    {
        $return = str_replace(['.'], ['_'], $proxy->getDomain());
        if ('/' !== $path) {
            $return .= str_replace(['/'], ['_'], $path);
        }

        return $return;
    }
}
