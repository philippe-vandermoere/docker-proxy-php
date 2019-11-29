<?php

/**
 * @author Philippe VANDERMOERE <philippe@wizaplace.com>
 * @copyright Copyright (C) Philippe VANDERMOERE
 * @license MIT
 */

declare(strict_types=1);

namespace Test\App\Twig;

use PHPUnit\Framework\TestCase;
use Faker\Factory as FakerFactory;
use App\Validator\Validator;

class ValidatorTest extends TestCase
{
    public function testValidatePort()
    {
        static::assertEquals(false, Validator::validatePort(mt_rand(-PHP_INT_MAX, 0)));
        static::assertEquals(false, Validator::validatePort(mt_rand(65536, PHP_INT_MAX)));
        static::assertEquals(true, Validator::validatePort(mt_rand(1, 65535)));
    }

    public function testValidateIp()
    {
        $faker = FakerFactory::create();
        static::assertEquals(false, Validator::validateIpv4($faker->text));
        static::assertEquals(false, Validator::validateIpv4('350.1.1.1'));
        static::assertEquals(true, Validator::validateIpv4($faker->ipv4));
    }

    public function testValidateDomain()
    {
        $faker = FakerFactory::create();
        static::assertEquals(false, Validator::validateDomain($faker->text));
        static::assertEquals(true, Validator::validateDomain($faker->domainName));
    }
}
