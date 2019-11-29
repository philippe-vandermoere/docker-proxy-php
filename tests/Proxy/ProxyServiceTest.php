<?php

/**
 * @author Philippe VANDERMOERE <philippe@wizaplace.com>
 * @copyright Copyright (C) Philippe VANDERMOERE
 * @license MIT
 */

declare(strict_types=1);

namespace Test\App\Proxy;

use App\Certificate\Certificate;
use App\Certificate\CertificateService;
use App\Proxy\NginxService;
use App\Proxy\Proxy;
use App\Proxy\ProxyCollection;
use App\Proxy\Server;
use PhilippeVandermoere\DockerPhpSdk\Container\Container;
use PhilippeVandermoere\DockerPhpSdk\Container\ContainerCollection;
use PhilippeVandermoere\DockerPhpSdk\Container\ContainerService;
use PhilippeVandermoere\DockerPhpSdk\Container\Label;
use PhilippeVandermoere\DockerPhpSdk\Container\LabelCollection;
use PhilippeVandermoere\DockerPhpSdk\Network\NetworkCollection;
use PhilippeVandermoere\DockerPhpSdk\Container\NetworkCollection as ContainerNetworkCollection;
use PhilippeVandermoere\DockerPhpSdk\Container\Network as ContainerNetwork;
use PhilippeVandermoere\DockerPhpSdk\DockerService;
use PhilippeVandermoere\DockerPhpSdk\Network\Network;
use PhilippeVandermoere\DockerPhpSdk\Network\NetworkService;
use PHPUnit\Framework\TestCase;
use App\Proxy\ProxyService;
use Faker\Factory as FakerFactory;
use Test\App\Tools;

class ProxyServiceTest extends TestCase
{
    public function testConstruct(): void
    {
        $faker = FakerFactory::create();
        $proxyService = new ProxyService(
            $dockerService = $this->createMock(DockerService::class),
            $nginxService = $this->createMock(NginxService::class),
            $certificateService = $this->createMock(CertificateService::class),
            $dockerLabelDomain = $faker->word,
            $dockerLabelPort = $faker->word,
            $dockerLabelSsl = $faker->word,
            $dockerLabelPath = $faker->word,
            $dockerLabelCertificateProviderPrefix = $faker->word
        );

        static::assertEquals(
            80,
            Tools::getConstant($proxyService, 'DEFAULT_HTTP_PORT')
        );
        static::assertEquals(
            $dockerService,
            Tools::getPropertyValue($proxyService, 'dockerService')
        );

        static::assertEquals(
            $nginxService,
            Tools::getPropertyValue($proxyService, 'nginxService')
        );

        static::assertEquals(
            $certificateService,
            Tools::getPropertyValue($proxyService, 'certificateService')
        );

        static::assertEquals(
            $dockerLabelDomain,
            Tools::getPropertyValue($proxyService, 'dockerLabelDomain')
        );

        static::assertEquals(
            $dockerLabelPort,
            Tools::getPropertyValue($proxyService, 'dockerLabelPort')
        );

        static::assertEquals(
            $dockerLabelSsl,
            Tools::getPropertyValue($proxyService, 'dockerLabelSsl')
        );

        static::assertEquals(
            $dockerLabelPath,
            Tools::getPropertyValue($proxyService, 'dockerLabelPath')
        );

        static::assertEquals(
            $dockerLabelCertificateProviderPrefix,
            Tools::getPropertyValue($proxyService, 'dockerLabelCertificateProviderPrefix')
        );
    }

    public function testGetProxyCollection(): void
    {
        $faker = FakerFactory::create();
        $proxyService = $containerService = $this
            ->getMockBuilder(ProxyService::class)
            ->setConstructorArgs(
                [
                    $dockerService = $this->createMock(DockerService::class),
                    $this->createMock(NginxService::class),
                    $this->createMock(CertificateService::class),
                    $dockerLabelDomain = $faker->word,
                    $dockerLabelPort = $faker->word,
                    $faker->word,
                    $dockerLabelPath = $faker->word,
                    $faker->word
                ]
            )
            ->onlyMethods(['listDockerContainer', 'getProxyNetwork', 'getCertificate'])
            ->getMock()
        ;

        $proxyNetwork = new Network(
            $proxyNetworkId = $faker->uuid,
            $faker->word,
            $faker->word,
            (bool) mt_rand(0, 1),
            (bool) mt_rand(0, 1),
            new LabelCollection()
        );

        $containerCollection = new ContainerCollection();
        for ($i = 0; $i <= 5; $i++) {
            $containerCollection[] = new Container(
                $id = $faker->uuid,
                $faker->word,
                $faker->word,
            );
        }

        $proxyCollection = new ProxyCollection();
        for ($i = 0; $i <= 5; $i++) {
            $domain = $faker->domainName;
            $proxy = new Proxy($domain);
            for ($j = 0; $j <= 10; $j++) {
                $container = new Container(
                    $id = $faker->uuid,
                    $name = $faker->word,
                    $faker->word
                );

                $labelCollection = new LabelCollection();
                $labelCollection[] = new Label($dockerLabelDomain, $domain);

                $port = 80;
                if (0 === $j % 3) {
                    $port = mt_rand(1, 65535);
                    $labelCollection[] = new Label($dockerLabelPort, (string) $port);
                }

                $path = '/';
                if (0 === $j % 4) {
                    $path = '/' . $faker->slug;
                    $labelCollection[] = new Label($dockerLabelPath, $path);
                }

                $network = new ContainerNetwork(
                    $proxyNetworkId,
                    $faker->word,
                    $ip = $faker->ipv4,
                );

                $container
                    ->setNetworks(new ContainerNetworkCollection([$network]))
                    ->setLabels($labelCollection)
                ;

                $containerCollection[] = $container;
                $proxy->addServer(
                    new Server(
                        $id,
                        $name,
                        $ip,
                        $port
                    ),
                    $path
                );
            }

            $proxyCollection[] = $proxy;
        }

        $domain = $faker->domainName;
        $proxy = new Proxy($domain);
        $container = new Container(
            $id = $faker->uuid,
            $name = $faker->word,
            $faker->word
        );
        $label = new Label($dockerLabelDomain, $domain);
        $container->setLabels(new LabelCollection([$label]));
        $containerCollection[] = $container;
        $proxyCollection[] = $proxy;
        $proxy->addServer(
            new Server(
                $id,
                $name,
                $ip = $faker->ipv4,
                80
            )
        );

        $dockerService
            ->method('getNetworkService')
            ->willReturn($networkService = $this->createMock(NetworkService::class))
        ;

        $dockerService
            ->method('getContainerService')
            ->willReturn($containerService = $this->createMock(ContainerService::class))
        ;

        $networkService
            ->method('connectContainer')
            ->willReturn($networkService)
        ;

        $containerReload = new Container(
            $container->getId(),
            $container->getName(),
            $container->getImage()
        );

        $network = new ContainerNetwork(
            $proxyNetworkId,
            $faker->word,
            $ip
        );

        $containerReload
            ->setLabels($container->getLabels())
            ->setNetworks(new ContainerNetworkCollection([$network]))
        ;

        $containerService
            ->method('get')
            ->willReturn($containerReload)
        ;

        $proxyService
            ->method('getProxyNetwork')
            ->willReturn($proxyNetwork)
        ;

        $proxyService
            ->method('listDockerContainer')
            ->willReturn($containerCollection)
        ;

        $proxyService
            ->method('getCertificate')
            ->willReturn(null)
        ;

        $proxyService
            ->expects($this->once())
            ->method('getProxyNetwork')
        ;

        $proxyService
            ->expects($this->once())
            ->method('listDockerContainer')
        ;

        $dockerService
            ->expects($this->once())
            ->method('getNetworkService')
        ;

        $dockerService
            ->expects($this->once())
            ->method('getContainerService')
        ;

        $networkService
            ->expects($this->once())
            ->method('connectContainer')
            ->with(
                $proxyNetworkId,
                $container->getId()
            )
        ;

        $containerService
            ->expects($this->once())
            ->method('get')
            ->with($container->getId())
        ;

        static::assertEquals($proxyCollection, $proxyService->getProxyCollection());
    }

    public function testConfigureProxy(): void
    {
        $faker = FakerFactory::create();
        $proxyService = new ProxyService(
            $this->createMock(DockerService::class),
            $nginxService = $this->createMock(NginxService::class),
            $this->createMock(CertificateService::class),
            $faker->word,
            $faker->word,
            $faker->word,
            $faker->word,
            $faker->word
        );

        $nginxService
            ->method('createProxyVirtualHost')
            ->willReturn($nginxService)
        ;

        $nginxService
            ->method('createHomepage')
            ->willReturn($nginxService)
        ;

        $nginxService
            ->method('deleteOldProxyVirtualHost')
            ->willReturn($nginxService)
        ;

        $proxyCollection = new ProxyCollection();
        for ($i = 0; $i <= 10; $i++) {
            $proxy = $this->createMock(Proxy::class);
            $proxyCollection[] = $proxy;

            $nginxService
                ->expects($this->once())
                ->method('createProxyVirtualHost')
                ->with($proxy)
            ;
        }

        $nginxService
            ->expects($this->once())
            ->method('createHomepage')
            ->with($proxyCollection)
        ;

        $nginxService
            ->expects($this->once())
            ->method('deleteOldProxyVirtualHost')
            ->with($proxyCollection)
        ;

        static::assertEquals(
            $proxyService,
            $proxyService->configureProxy($proxyCollection)
        );
    }

    public function testReloadProxy(): void
    {
        $faker = FakerFactory::create();
        $proxyService = new ProxyService(
            $dockerService = $this->createMock(DockerService::class),
            $this->createMock(NginxService::class),
            $this->createMock(CertificateService::class),
            $faker->word,
            $faker->word,
            $faker->word,
            $faker->word,
            $faker->word
        );

        $container = new Container(
            $id = $faker->uuid,
            $faker->word,
            $faker->word
        );

        $container->setLabels(
            new LabelCollection(
                [
                    new Label('com.docker.compose.project', 'docker-proxy'),
                    new Label('com.docker.compose.service', 'nginx'),
                ]
            )
        );

        $containerService = $this->createMock(ContainerService::class);
        $dockerService
            ->method('getContainerService')
            ->willReturn($containerService)
        ;

        $containerService
            ->method('list')
            ->willReturn(new ContainerCollection([$container]))
        ;

        $containerService
            ->method('executeCommand')
            ->willReturn($faker->text)
        ;

        $dockerService
            ->expects($this->exactly(2))
            ->method('getContainerService')
        ;

        $containerService
            ->expects($this->once())
            ->method('list')
        ;

        $containerService
            ->expects($this->once())
            ->method('executeCommand')
            ->with(
                $id,
                ['nginx', '-s', 'reload']
            )
        ;

        static::expectOutputString('Proxy: Reload nginx configuration.' . PHP_EOL);
        static::assertEquals($proxyService, $proxyService->reloadProxy());
    }

    public function testReloadProxyError(): void
    {
        $faker = FakerFactory::create();
        $proxyService = new ProxyService(
            $dockerService = $this->createMock(DockerService::class),
            $this->createMock(NginxService::class),
            $this->createMock(CertificateService::class),
            $faker->word,
            $faker->word,
            $faker->word,
            $faker->word,
            $faker->word
        );

        $containerService = $this->createMock(ContainerService::class);
        $dockerService
            ->method('getContainerService')
            ->willReturn($containerService)
        ;

        $containerService
            ->method('list')
            ->willReturn(new ContainerCollection())
        ;

        $containerService
            ->method('executeCommand')
            ->willReturn($faker->text)
        ;

        $dockerService
            ->expects($this->exactly(2))
            ->method('getContainerService')
        ;

        $containerService
            ->expects($this->once())
            ->method('list')
        ;

        $containerService
            ->expects($this->never())
            ->method('executeCommand')
        ;

        static::expectException(\RuntimeException::class);
        static::expectExceptionMessage('Unable to find docker-proxy nginx container.');
        static::expectOutputString('Proxy: Reload nginx configuration.' . PHP_EOL);
        $proxyService->reloadProxy();
    }

    public function testGetProxyNetwork(): void
    {
        $faker = FakerFactory::create();
        $proxyService = $containerService = $this
            ->getMockBuilder(ProxyService::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['listDockerNetwork'])
            ->getMock()
        ;

        $network = new Network(
            $faker->uuid,
            $faker->word,
            $faker->word,
            (bool) mt_rand(0, 1),
            (bool) mt_rand(0, 1),
            new LabelCollection([new Label('com.docker.compose.project', 'docker-proxy')])
        );

        $proxyService
            ->method('listDockerNetwork')
            ->willReturn(new NetworkCollection([$network]))
        ;

        $proxyService
            ->expects($this->once())
            ->method('listDockerNetwork')
        ;

        static::assertEquals(
            $network,
            Tools::callProtectedMethod($proxyService, 'getProxyNetwork')
        );
    }

    public function testGetProxyNetworkError(): void
    {
        $proxyService = $containerService = $this
            ->getMockBuilder(ProxyService::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['listDockerNetwork'])
            ->getMock()
        ;

        $proxyService
            ->method('listDockerNetwork')
            ->willReturn(new NetworkCollection())
        ;

        $proxyService
            ->expects($this->once())
            ->method('listDockerNetwork')
        ;

        static::expectException(\RuntimeException::class);
        static::expectExceptionMessage('Unable to find docker-proxy Network.');
        Tools::callProtectedMethod($proxyService, 'getProxyNetwork');
    }

    public function testGetCertificateSsl(): void
    {
        $faker = FakerFactory::create();
        $proxyService = new ProxyService(
            $this->createMock(DockerService::class),
            $this->createMock(NginxService::class),
            $certificateService = $this->createMock(CertificateService::class),
            $faker->word,
            $faker->word,
            $dockerLabelSsL = $faker->word,
            $faker->word,
            $dockerLabelCertificateProviderPrefix = $faker->word
        );

        $domain = $faker->domainName;
        $container = new Container($faker->uuid, $faker->word, $faker->word);

        $labelCollection = new LabelCollection();
        $labelCollection[] = new Label($dockerLabelSsL, 'true');
        $options = [];
        for ($i = 0; $i <= 10; $i++) {
            $name = $faker->word;
            $value = $faker->word;
            $options[$name] = $value;
            $labelCollection[] = new Label($dockerLabelCertificateProviderPrefix . '.' . $name, $value);
        }

        $container->setLabels($labelCollection);

        $certificateService
            ->method('getCertificate')
            ->willReturn($certificate = $this->createMock(Certificate::class))
        ;

        $certificateService
            ->expects($this->once())
            ->method('getCertificate')
            ->with(
                $domain,
                $options
            )
        ;

        static::assertEquals(
            $certificate,
            Tools::callProtectedMethod(
                $proxyService,
                'getCertificate',
                [$domain, $container]
            )
        );
    }

    public function testGetCertificateSslDisable(): void
    {
        $faker = FakerFactory::create();
        $proxyService = new ProxyService(
            $this->createMock(DockerService::class),
            $this->createMock(NginxService::class),
            $certificateService = $this->createMock(CertificateService::class),
            $faker->word,
            $faker->word,
            $dockerLabelSsL = $faker->word,
            $faker->word,
            $dockerLabelCertificateProviderPrefix = $faker->word
        );

        $container = new Container($faker->uuid, $faker->word, $faker->word);

        static::assertEquals(
            null,
            Tools::callProtectedMethod(
                $proxyService,
                'getCertificate',
                [$faker->domainName, $container]
            )
        );

        $container->setLabels(
            new LabelCollection(
                [
                    new Label($dockerLabelSsL, 'false')
                ]
            )
        );

        static::assertEquals(
            null,
            Tools::callProtectedMethod(
                $proxyService,
                'getCertificate',
                [$faker->domainName, $container]
            )
        );
    }

    public function testGetCertificateError(): void
    {
        $faker = FakerFactory::create();
        $proxyService = new ProxyService(
            $this->createMock(DockerService::class),
            $this->createMock(NginxService::class),
            $certificateService = $this->createMock(CertificateService::class),
            $faker->word,
            $faker->word,
            $dockerLabelSsL = $faker->word,
            $faker->word,
            $dockerLabelCertificateProviderPrefix = $faker->word
        );

        $domain = $faker->domainName;
        $container = new Container($faker->uuid, $faker->word, $faker->word);

        $labelCollection = new LabelCollection();
        $labelCollection[] = new Label($dockerLabelSsL, 'true');
        $options = [];
        for ($i = 0; $i <= 10; $i++) {
            $name = $faker->word;
            $value = $faker->word;
            $options[$name] = $value;
            $labelCollection[] = new Label($dockerLabelCertificateProviderPrefix . '.' . $name, $value);
        }

        $container->setLabels($labelCollection);

        $certificateService
            ->method('getCertificate')
            ->willThrowException($throwable = new \Exception($faker->word))
        ;

        $certificateService
            ->expects($this->once())
            ->method('getCertificate')
            ->with(
                $domain,
                $options
            )
        ;

        static::expectOutputString(
            sprintf(
                "Error: %s in file %s in line %s\n",
                $throwable->getMessage(),
                $throwable->getFile(),
                $throwable->getLine()
            )
        );

        static::assertEquals(
            null,
            Tools::callProtectedMethod(
                $proxyService,
                'getCertificate',
                [$domain, $container]
            )
        );
    }

    public function testListDockerContainer(): void
    {
        $faker = FakerFactory::create();
        $proxyService = new ProxyService(
            $dockerService = $this->createMock(DockerService::class),
            $this->createMock(NginxService::class),
            $this->createMock(CertificateService::class),
            $faker->word,
            $faker->word,
            $faker->word,
            $faker->word,
            $faker->word
        );

        $containerCollection = new ContainerCollection();
        for ($i = 0; $i <= 10; $i++) {
            $containerCollection[] = new Container(
                $faker->uuid,
                $faker->word,
                $faker->word,
            );
        }

        $containerService = $this->createMock(ContainerService::class);
        $dockerService
            ->method('getContainerService')
            ->willReturn($containerService)
        ;

        $containerService
            ->method('list')
            ->willReturnOnConsecutiveCalls(
                $containerCollection,
                new ContainerCollection()
            )
        ;

        $dockerService
            ->expects($this->exactly(2))
            ->method('getContainerService')
        ;

        $containerService
            ->expects($this->exactly(2))
            ->method('list')
        ;

        static::assertEquals(
            $containerCollection,
            Tools::callProtectedMethod($proxyService, 'listDockerContainer')
        );

        static::assertEquals(
            $containerCollection,
            Tools::callProtectedMethod($proxyService, 'listDockerContainer')
        );

        static::assertEquals(
            new ContainerCollection(),
            Tools::callProtectedMethod($proxyService, 'listDockerContainer', [false])
        );
    }

    public function testListDockerNetwork(): void
    {
        $faker = FakerFactory::create();
        $proxyService = new ProxyService(
            $dockerService = $this->createMock(DockerService::class),
            $this->createMock(NginxService::class),
            $this->createMock(CertificateService::class),
            $faker->word,
            $faker->word,
            $faker->word,
            $faker->word,
            $faker->word
        );

        $networkCollection = new NetworkCollection();
        for ($i = 0; $i <= 10; $i++) {
            $networkCollection[] = new Network(
                $faker->uuid,
                $faker->word,
                $faker->word,
                (bool) mt_rand(0, 1),
                (bool) mt_rand(0, 1),
                new LabelCollection()
            );
        }

        $networkService = $this->createMock(NetworkService::class);

        $dockerService
            ->method('getNetworkService')
            ->willReturn($networkService)
        ;

        $networkService
            ->method('list')
            ->willReturnOnConsecutiveCalls(
                $networkCollection,
                new NetworkCollection()
            )
        ;

        $dockerService
            ->expects($this->exactly(2))
            ->method('getNetworkService')
        ;

        $networkService
            ->expects($this->exactly(2))
            ->method('list')
        ;

        static::assertEquals(
            $networkCollection,
            Tools::callProtectedMethod($proxyService, 'listDockerNetwork')
        );

        static::assertEquals(
            $networkCollection,
            Tools::callProtectedMethod($proxyService, 'listDockerNetwork')
        );

        static::assertEquals(
            new NetworkCollection(),
            Tools::callProtectedMethod($proxyService, 'listDockerNetwork', [false])
        );
    }
}
