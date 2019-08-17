<?php
/**
 * @author Philippe VANDERMOERE <philippe@wizaplace.com>
 * @copyright Copyright (C) Philippe VANDERMOERE
 * @license MIT
 */

declare(strict_types=1);

namespace App\Proxy;

use Symfony\Component\Finder\Finder;
use Twig\Environment as Twig;

class NginxService
{
    protected const TEMPLATE_PROXY_FILE = 'nginx/virtualHost/proxy.conf';

    protected const TEMPLATE_DEFAULT_FILE = 'nginx/virtualHost/default.conf';

    protected const TEMPLATE_HOMEPAGE_FILE = 'nginx/index.html';

    protected const VIRTUAL_HOST_HOMEPAGE_FILE = 'default.conf';

    /** @var Twig */
    protected $twigService;

    /** @var string */
    protected $virtualHostDirectory;

    /** @var string */
    protected $homepageDirectory;

    public function __construct(Twig $twigService, string $virtualHostDirectory, string $homepageDirectory)
    {
        $this->twigService = $twigService;

        if (false === is_dir($virtualHostDirectory)) {
            throw new \InvalidArgumentException("'$virtualHostDirectory' is not a directory.");
        }

        $this->virtualHostDirectory = rtrim($virtualHostDirectory, '/');

        if (false === is_dir($homepageDirectory)) {
            throw new \InvalidArgumentException("'$homepageDirectory' is not a directory.");
        }

        $this->homepageDirectory = rtrim($homepageDirectory, '/');
    }

    public function createProxyVirtualHost(Proxy $proxy): self
    {
        file_put_contents(
            $this->virtualHostDirectory . '/' . $proxy->getDomain() . '.conf',
            $this->twigService->render(static::TEMPLATE_PROXY_FILE, ['proxy' => $proxy])
        );

        return $this;
    }

    public function createHomepage(ProxyCollection $proxyCollection): self
    {
        file_put_contents(
            $this->virtualHostDirectory . '/' . static::VIRTUAL_HOST_HOMEPAGE_FILE,
            $this->twigService->render(
                static::TEMPLATE_DEFAULT_FILE,
                ['document_root' => $this->homepageDirectory]
            )
        );

        file_put_contents(
            $this->homepageDirectory . '/index.html',
            $this->twigService->render(
                static::TEMPLATE_HOMEPAGE_FILE,
                ['proxys' => $proxyCollection]
            )
        );

        return $this;
    }

    public function deleteOldProxyVirtualHost(ProxyCollection $proxyCollection): self
    {
        $finder = new Finder();
        $finder->files()
            ->in($this->virtualHostDirectory)
            ->name('*.conf')
            ->notName(static::VIRTUAL_HOST_HOMEPAGE_FILE)
        ;

        foreach ($proxyCollection as $proxy) {
            $finder->notName($proxy->getDomain() . '.conf');
        }

        foreach ($finder as $file) {
            unlink($file->getPathname());
        }

        return $this;
    }
}
