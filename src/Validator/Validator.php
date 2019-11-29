<?php

/**
 * @author Philippe VANDERMOERE <philippe@wizaplace.com>
 * @copyright Copyright (C) Philippe VANDERMOERE
 * @license MIT
 */

declare(strict_types=1);

namespace App\Validator;

class Validator
{
    protected const REGEX_IPV4 = '/\b((25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)(\.|$)){4}\b/';
    protected const REGEX_DOMAIN = '/(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z0-9][a-z0-9-]{0,61}[a-z0-9]/';

    public static function validatePort(int $port): bool
    {
        if (1 > $port || 65535 < $port) {
            return false;
        }

        return true;
    }

    public static function validateIpv4(string $ip): bool
    {
        if (1 === preg_match(static::REGEX_IPV4, $ip)) {
            return true;
        }

        return false;
    }

    public static function validateDomain(string $domain): bool
    {
        if (1 === preg_match(static::REGEX_DOMAIN, $domain)) {
            return true;
        }

        return false;
    }
}
