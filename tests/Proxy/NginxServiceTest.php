<?php

/**
 * @author Philippe VANDERMOERE <philippe@wizaplace.com>
 * @copyright Copyright (C) Philippe VANDERMOERE
 * @license MIT
 */

declare(strict_types=1);

namespace Test\App\Proxy;

use App\Proxy\Proxy;
use App\Proxy\ProxyCollection;
use PHPUnit\Framework\TestCase;
use App\Proxy\NginxService;
use Symfony\Component\Filesystem\Filesystem;
use Twig\Environment as Twig;
use Faker\Factory as FakerFactory;
use Test\App\Tools;

class NginxServiceTest extends TestCase
{
    public function testConstruct(): void
    {
        $nginxService = new NginxService(
            $twigService = $this->createMock(Twig::class),
            $virtualHostDirectory = sys_get_temp_dir(),
            $homepageDirectory = sys_get_temp_dir()
        );

        static::assertEquals(
            'nginx/virtualHost/proxy.conf',
            Tools::getConstant($nginxService, 'TEMPLATE_PROXY_FILE')
        );
        static::assertEquals(
            'nginx/virtualHost/default.conf',
            Tools::getConstant($nginxService, 'TEMPLATE_DEFAULT_FILE')
        );
        static::assertEquals(
            'nginx/index.html',
            Tools::getConstant($nginxService, 'TEMPLATE_HOMEPAGE_FILE')
        );
        static::assertEquals(
            'default.conf',
            Tools::getConstant($nginxService, 'VIRTUAL_HOST_HOMEPAGE_FILE')
        );

        static::assertEquals(
            $twigService,
            Tools::getPropertyValue($nginxService, 'twigService')
        );

        static::assertEquals(
            $virtualHostDirectory,
            Tools::getPropertyValue($nginxService, 'virtualHostDirectory')
        );

        static::assertEquals(
            $homepageDirectory,
            Tools::getPropertyValue($nginxService, 'homepageDirectory')
        );
    }

    public function testConstructErrorVirtualHostDirectory(): void
    {
        $faker = FakerFactory::create();
        $virtualHostDirectory = sys_get_temp_dir() . '/' . $faker->word;

        static::expectException(\InvalidArgumentException::class);
        static::expectExceptionMessage("'$virtualHostDirectory' is not a directory.");

        new NginxService(
            $this->createMock(Twig::class),
            $virtualHostDirectory,
            sys_get_temp_dir()
        );
    }

    public function testConstructErrorHomepageDirectory(): void
    {
        $faker = FakerFactory::create();
        $homepageDirectory = sys_get_temp_dir() . '/' . $faker->word;

        static::expectException(\InvalidArgumentException::class);
        static::expectExceptionMessage("'$homepageDirectory' is not a directory.");

        new NginxService(
            $this->createMock(Twig::class),
            sys_get_temp_dir(),
            $homepageDirectory
        );
    }

    public function testCreateProxyVirtualHost(): void
    {
        $faker = FakerFactory::create();
        $fileSystem = new Filesystem();
        $twigService = $this->createMock(Twig::class);
        $virtualHostDirectory = sys_get_temp_dir() . '/' . $faker->word;
        $domain = $faker->domainName;
        $proxy = new Proxy($domain);

        $fileSystem->mkdir($virtualHostDirectory);
        $nginxService = new NginxService(
            $twigService,
            $virtualHostDirectory,
            sys_get_temp_dir()
        );

        $twigService
            ->method('render')
            ->willReturn($nginxVhost = $faker->text)
        ;

        $twigService
            ->expects($this->once())
            ->method('render')
            ->with(
                Tools::getConstant($nginxService, 'TEMPLATE_PROXY_FILE'),
                ['proxy' => $proxy]
            )
        ;

        static::assertEquals(
            $nginxService,
            $nginxService->createProxyVirtualHost($proxy)
        );

        static::assertEquals(
            $nginxVhost,
            file_get_contents($virtualHostDirectory . '/' . $domain . '.conf')
        );

        $fileSystem->remove($virtualHostDirectory);
    }

    public function testCreateHomepage(): void
    {
        $faker = FakerFactory::create();
        $fileSystem = new Filesystem();
        $twigService = $this->createMock(Twig::class);
        $virtualHostDirectory = sys_get_temp_dir() . '/vhd_' . $faker->word;
        $homepageDirectory = sys_get_temp_dir() . '/hd_' . $faker->word;
        $proxyCollection = new ProxyCollection();

        $fileSystem->mkdir([$virtualHostDirectory, $homepageDirectory]);

        $nginxService = new NginxService(
            $twigService,
            $virtualHostDirectory,
            $homepageDirectory
        );

        $twigService
            ->method('render')
            ->willReturnOnConsecutiveCalls(
                $nginxVhost = $faker->text,
                $html = $faker->randomHtml()
            )
        ;

        $twigService
            ->expects($this->exactly(2))
            ->method('render')
            ->withConsecutive(
                [
                    Tools::getConstant($nginxService, 'TEMPLATE_DEFAULT_FILE'),
                    ['document_root' => $homepageDirectory]
                ],
                [
                    Tools::getConstant($nginxService, 'TEMPLATE_HOMEPAGE_FILE'),
                    ['proxys' => $proxyCollection]
                ]
            )
        ;

        static::assertEquals(
            $nginxService,
            $nginxService->createHomepage($proxyCollection)
        );

        static::assertEquals(
            $nginxVhost,
            file_get_contents(
                $virtualHostDirectory
                . '/'
                . Tools::getConstant($nginxService, 'VIRTUAL_HOST_HOMEPAGE_FILE')
            )
        );

        static::assertEquals(
            $html,
            file_get_contents($homepageDirectory . '/index.html')
        );

        $fileSystem->remove([$virtualHostDirectory, $homepageDirectory]);
    }

    public function testDeleteOldProxyVirtualHost(): void
    {
        $faker = FakerFactory::create();
        $fileSystem = new Filesystem();
        $twigService = $this->createMock(Twig::class);
        $virtualHostDirectory = sys_get_temp_dir() . '/' . $faker->word;
        $fileSystem->mkdir($virtualHostDirectory);

        $keep = [];
        $proxyCollection = new ProxyCollection();
        for ($i = 0; $i <= 5; $i++) {
            $domain = $faker->domainName;
            $proxyCollection[] = new Proxy($domain);
            $file = $virtualHostDirectory . '/' . $domain . '.conf';
            $fileSystem->touch($file);
            $keep[] = $file;
        }

        $deleted = [];
        for ($i = 0; $i <= 5; $i++) {
            $domain = 'delete.' . $faker->domainName;
            $file = $virtualHostDirectory . '/' . $domain . '.conf';
            $fileSystem->touch($file);
            $deleted[] = $file;
        }

        $nginxService = new NginxService(
            $twigService,
            $virtualHostDirectory,
            sys_get_temp_dir()
        );

        static::assertEquals(
            $nginxService,
            $nginxService->deleteOldProxyVirtualHost($proxyCollection)
        );

        foreach ($keep as $file) {
            static::assertEquals(true, $fileSystem->exists($file));
        }

        foreach ($deleted as $file) {
            static::assertEquals(false, $fileSystem->exists($file));
        }

        $fileSystem->remove($virtualHostDirectory);
    }
}
