<?php
/**
 * @author Philippe VANDERMOERE <philippe@wizaplace.com>
 * @copyright Copyright (C) Philippe VANDERMOERE
 * @license MIT
 */

declare(strict_types=1);

namespace App\Proxy;

use App\Validator\Validator;

class Server
{
    /** @var string */
    protected $id;

    /** @var string */
    protected $name;

    /** @var string */
    protected $ip;

    /** @var int */
    protected $port;

    public function __construct(string $id, string $name, string $ip, int $port)
    {
        if (false === Validator::validateIpv4($ip)) {
            throw new \InvalidArgumentException('ip `' . $ip . '` must respect the RFC');
        }

        if (false === Validator::validatePort($port)) {
            throw new \InvalidArgumentException('port `' . $port .'` must be between 1 and 65535.');
        }

        $this->id = $id;
        $this->name = $name;
        $this->ip = $ip;
        $this->port = $port;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getIp(): string
    {
        return $this->ip;
    }

    public function getPort(): int
    {
        return $this->port;
    }
}
