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
use App\Twig\TwigFactory;
use Twig\Environment as Twig;
use Twig\Extension\ExtensionInterface as TwigExtensionInterface;

class TwigFactoryTest extends TestCase
{
    public function testCreate()
    {
        $twigFactory = new TwigFactory(sys_get_temp_dir());
        static::assertInstanceOf(Twig::class, $twigFactory->create());
        $this->assertEquals(
            sys_get_temp_dir(),
            $twigFactory->create()->getLoader()->getPaths()[0]
        );
    }

    public function testCreateWithExtensions()
    {
        $faker = FakerFactory::create();
        $twigExtensions = [];
        for ($i = 0; $i <= 10; $i++) {
            $twigExtensions[$i] = $this
                ->getMockBuilder(TwigExtensionInterface::class)
                ->setMockClassName($faker->word . '_' . $i)
                ->getMock()
            ;
        }

        $twigFactory = new TwigFactory(
            sys_get_temp_dir(),
            $twigExtensions
        );

        foreach ($twigExtensions as $twigExtension) {
            static::assertEquals(
                $twigExtension,
                $twigFactory->create()->getExtensions()[get_class($twigExtension)]
            );
        }
    }
}
