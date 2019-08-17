<?php
/**
 * @author Philippe VANDERMOERE <philippe@wizaplace.com>
 * @copyright Copyright (C) Philippe VANDERMOERE
 * @license MIT
 */

declare(strict_types=1);

namespace App\Certificate;

use App\Certificate\Provider\SelfSigned;

class CertificateService
{
    /** @var ProviderCollection */
    protected $providers;

    /** @var string */
    protected $certificateDirectory;

    public function __construct(iterable $providers, string $certificateDirectory)
    {
        $this->providers = new ProviderCollection($providers);
        if (false === \is_dir($certificateDirectory)) {
            throw new \InvalidArgumentException($certificateDirectory . ' is not a directory.');
        }

        $this->certificateDirectory = rtrim($certificateDirectory, '/');
    }

    public function getCertificate(string $domain, array $options): Certificate
    {
        $certificate = $this->createEmptyCertificate($domain);

        if ($certificate->isValid() && false === $certificate->isExpired()) {
            echo 'Certificate: The certificate is valid for domain ' . $domain . '.' . PHP_EOL;
            return $certificate;
        }

        $this->getProvider($options['name'] ?? null)->createCertificate($certificate, $options);

        if (false === $certificate->isValid()) {
            throw new \RuntimeException('Unable to create certificate for domain `' . $domain . '`.');
        }

        return $certificate;
    }

    protected function createEmptyCertificate(string $domain): Certificate
    {
        if (false === \is_dir($this->certificateDirectory . '/' . $domain)) {
            mkdir($this->certificateDirectory . '/' . $domain);
        }

        return new Certificate(
            $domain,
            $this->certificateDirectory . '/' . $domain . '/certificate.pem',
            $this->certificateDirectory . '/' . $domain . '/privatekey.pem'
        );
    }

    protected function getProvider(string $providerName = null): ProviderInterface
    {
        foreach ($this->providers as $provider) {
            if ($providerName === $provider->getName()) {
                return $provider;
            }
        }

        // fallback to SelfSigned provider
        return new SelfSigned();
    }
}
