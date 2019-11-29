<?php

/**
 * @author Philippe VANDERMOERE <philippe@wizaplace.com>
 * @copyright Copyright (C) Philippe VANDERMOERE
 * @license MIT
 */

declare(strict_types=1);

namespace App\Proxy;

use App\Certificate\Certificate;
use App\Certificate\CertificateService;
use PhilippeVandermoere\DockerPhpSdk\Container\ContainerCollection;
use PhilippeVandermoere\DockerPhpSdk\DockerService;
use PhilippeVandermoere\DockerPhpSdk\Container\Container;
use PhilippeVandermoere\DockerPhpSdk\Network\Network;
use PhilippeVandermoere\DockerPhpSdk\Network\NetworkCollection;

class ProxyService
{
    protected const DEFAULT_HTTP_PORT = 80;

    /** @var DockerService */
    protected $dockerService;

    /** @var NginxService */
    protected $nginxService;

    /** @var CertificateService */
    protected $certificateService;

    /** @var string */
    protected $dockerLabelDomain;

    /** @var string */
    protected $dockerLabelPort;

    /** @var string */
    protected $dockerLabelSsl;

    /** @var string */
    protected $dockerLabelPath;

    /** @var string */
    protected $dockerLabelCertificateProviderPrefix;

    /** @var ?ContainerCollection */
    protected $containerCollection;

    /** @var ?NetworkCollection */
    protected $networkCollection;

    public function __construct(
        DockerService $dockerService,
        NginxService $nginxService,
        CertificateService $certificateService,
        string $dockerLabelDomain,
        string $dockerLabelPort,
        string $dockerLabelSsl,
        string $dockerLabelPath,
        string $dockerLabelCertificateProviderPrefix
    ) {
        $this->dockerService = $dockerService;
        $this->nginxService = $nginxService;
        $this->certificateService = $certificateService;
        $this->dockerLabelDomain = $dockerLabelDomain;
        $this->dockerLabelPort = $dockerLabelPort;
        $this->dockerLabelPath = $dockerLabelPath;
        $this->dockerLabelSsl = $dockerLabelSsl;
        $this->dockerLabelCertificateProviderPrefix = $dockerLabelCertificateProviderPrefix;
    }

    public function getProxyCollection(): ProxyCollection
    {
        $proxyNetwork = $this->getProxyNetwork();
        $proxyCollection = new ProxyCollection();
        foreach ($this->listDockerContainer(false) as $container) {
            $domain = $container->getLabels()->getValue($this->dockerLabelDomain);
            if (null === $domain) {
                continue;
            }

            if (false === $proxyCollection->offsetExists($domain)) {
                $proxyCollection[] = new Proxy(
                    $domain,
                    $this->getCertificate($domain, $container)
                );
            }

            if (false ===  $container->getNetworks()->offsetExists($proxyNetwork->getId())) {
                $this->dockerService->getNetworkService()->connectContainer(
                    $proxyNetwork->getId(),
                    $container->getId()
                );

                // reload object from docker API
                $container = $this->dockerService->getContainerService()->get($container->getId());
            }

            $proxyCollection
                ->offsetGet($domain)
                ->addServer(
                    new Server(
                        $container->getId(),
                        $container->getName(),
                        $container->getNetworks()[$proxyNetwork->getId()]->getIp(),
                        (int) ($container->getLabels()->getValue($this->dockerLabelPort) ?? static::DEFAULT_HTTP_PORT)
                    ),
                    $container->getLabels()->getValue($this->dockerLabelPath) ?? '/'
                )
            ;
        }

        return $proxyCollection;
    }

    public function configureProxy(ProxyCollection $proxyCollection): self
    {
        foreach ($proxyCollection as $proxy) {
            $this->nginxService->createProxyVirtualHost($proxy);
        }

        $this->nginxService
            ->createHomepage($proxyCollection)
            ->deleteOldProxyVirtualHost($proxyCollection)
        ;

        return $this;
    }

    public function reloadProxy(): self
    {
        echo 'Proxy: Reload nginx configuration.' . PHP_EOL;
        $this->dockerService
            ->getContainerService()
            ->executeCommand(
                $this->getNginxContainer()->getId(),
                ['nginx', '-s', 'reload']
            )
        ;

        return $this;
    }

    protected function getNginxContainer(): Container
    {
        foreach ($this->listDockerContainer() as $container) {
            if ('docker-proxy' === $container->getLabels()->getValue('com.docker.compose.project')
                && 'nginx' === $container->getLabels()->getValue('com.docker.compose.service')
            ) {
                return $container;
            }
        }

        throw new \RuntimeException('Unable to find docker-proxy nginx container.');
    }

    protected function getProxyNetwork(): Network
    {
        foreach ($this->listDockerNetwork() as $network) {
            if ('docker-proxy' === $network->getLabels()->getValue('com.docker.compose.project')) {
                return $network;
            }
        }

        throw new \RuntimeException('Unable to find docker-proxy Network.');
    }

    protected function getCertificate(string $domain, Container $container): ?Certificate
    {
        if (false === filter_var($container->getLabels()->getValue($this->dockerLabelSsl), FILTER_VALIDATE_BOOLEAN)) {
            return null;
        }

        $options = [];
        foreach ($container->getLabels() as $label) {
            if (1 === preg_match(
                '/' . $this->dockerLabelCertificateProviderPrefix . '.(.*)/',
                $label->getName(),
                $matches
            )
            ) {
                $options[$matches[1]] = $label->getValue();
            }
        }

        try {
            return $this->certificateService->getCertificate($domain, $options);
        } catch (\Throwable $throwable) {
            echo sprintf(
                "Error: %s in file %s in line %s\n",
                $throwable->getMessage(),
                $throwable->getFile(),
                $throwable->getLine()
            );
            return null;
        }
    }

    protected function listDockerContainer(bool $cache = true): ContainerCollection
    {
        if (false === $cache || false === $this->containerCollection instanceof ContainerCollection) {
            $this->containerCollection = $this->dockerService->getContainerService()->list();
        }

        return $this->containerCollection;
    }

    protected function listDockerNetwork(bool $cache = true): NetworkCollection
    {
        if (false === $cache || false === $this->networkCollection instanceof NetworkCollection) {
            $this->networkCollection = $this->dockerService->getNetworkService()->list();
        }
        return $this->networkCollection;
    }
}
