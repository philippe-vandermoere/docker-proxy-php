<?php

/**
 * @author Philippe VANDERMOERE <philippe@wizaplace.com>
 * @copyright Copyright (C) Philippe VANDERMOERE
 * @license MIT
 */

declare(strict_types=1);

namespace App\Proxy;

use App\Certificate\Certificate;
use App\Validator\Validator;

class Proxy
{
    protected string $domain;
    protected ?Certificate $certificate;

    /** @var ServerCollection[]  */
    protected array $servers = [];

    public function __construct(string $domain, Certificate $certificate = null)
    {
        if (false === Validator::validateDomain($domain)) {
            throw new \InvalidArgumentException('domain `' . $domain . '` must be respect the RFC.');
        }

        $this->domain = $domain;
        $this->certificate = $certificate;
    }

    public function getDomain(): string
    {
        return $this->domain;
    }

    public function getCertificate(): ?Certificate
    {
        return $this->certificate;
    }

    public function addServer(Server $server, string $path = '/'): self
    {
        if (false === \array_key_exists($path, $this->servers)) {
            $this->servers[$path] = new ServerCollection();
        }

        $this->servers[$path][] = $server;

        return $this;
    }

    /** @return string[] */
    public function getPaths(): array
    {
        return array_keys($this->servers);
    }

    public function getServers(string $path = '/'): ServerCollection
    {
        if (true === \array_key_exists($path, $this->servers)
            && $this->servers[$path] instanceof ServerCollection
        ) {
            return $this->servers[$path];
        }

        return new ServerCollection();
    }

    public function hasSsl(): bool
    {
        return $this->certificate instanceof Certificate;
    }
}
