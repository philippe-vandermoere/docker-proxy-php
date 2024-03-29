<?php

/**
 * @author Philippe VANDERMOERE <philippe@wizaplace.com>
 * @copyright Copyright (C) Philippe VANDERMOERE
 * @license MIT
 */

declare(strict_types=1);

namespace App\Certificate;

use App\Validator\Validator;

class Certificate
{
    protected string $domain;
    protected string $certificateFilename;
    protected string $privateKeyFilename;
    protected string $certificateChainFilename;

    public function __construct(
        string $domain,
        string $certificateFilename,
        string $privateKeyFilename,
        string $certificateChainFilename
    ) {
        if (false === Validator::validateDomain($domain)) {
            throw new \InvalidArgumentException('domain `' . $domain . '` must be respect the RFC.');
        }

        $this->domain = $domain;
        $this->certificateFilename = $certificateFilename;
        $this->privateKeyFilename = $privateKeyFilename;
        $this->certificateChainFilename = $certificateChainFilename;
    }

    public function getDomain(): string
    {
        return $this->domain;
    }

    public function getCertificateFilename(): string
    {
        return $this->certificateFilename;
    }

    public function getPrivateKeyFilename(): string
    {
        return $this->privateKeyFilename;
    }

    public function getCertificateChainFilename(): string
    {
        return $this->certificateChainFilename;
    }

    public function hasCertificateChain(): bool
    {
        return true === \is_file($this->getCertificateChainFilename());
    }

    public function writeCertificate(string $certificate): self
    {
        return $this->writeFileContent($this->getCertificateFilename(), $certificate);
    }

    public function writePrivateKey(string $privateKey): self
    {
        return $this->writeFileContent($this->getPrivateKeyFilename(), $privateKey);
    }

    public function writeCertificateChain(string $certificateChain): self
    {
        $this->writeFileContent($this->getCertificateChainFilename(), $certificateChain);

        return $this;
    }

    public function getStartDate(): \DateTimeInterface
    {
        $data = $this->parseCertificate();
        $date = \DateTimeImmutable::createFromFormat('ymdHise', $data['validFrom']);
        if (false === $date) {
            throw new \RuntimeException('Unable to parse certificate start date.');
        }

        return $date;
    }

    public function getExpireDate(): \DateTimeInterface
    {
        $data = $this->parseCertificate();
        $date = \DateTimeImmutable::createFromFormat('ymdHise', $data['validTo']);
        if (false === $date) {
            throw new \RuntimeException('Unable to parse certificate expire date.');
        }

        return $date;
    }

    public function isExpired(\DateTimeInterface $date = null): bool
    {
        return ($this->getExpireDate() < ($date ?? new \DateTime('now')));
    }

    public function isValid(): bool
    {
        try {
            if ($this->domain !== $this->parseCertificate()['subject']['CN'] ?? '') {
                return false;
            }

            if (false === \openssl_x509_check_private_key(
                $this->getFileContent($this->getCertificateFilename()),
                $this->getFileContent($this->getPrivateKeyFilename())
            )
            ) {
                return false;
            }
        } catch (\Throwable $throwable) {
            return false;
        }

        return true;
    }

    /** @return mixed[] */
    protected function parseCertificate(): array
    {
        $certificate = \openssl_x509_parse($this->getFileContent($this->getCertificateFilename()));
        if (false === \is_array($certificate)) {
            throw new \RuntimeException('Unable to parse certificate.');
        }

        return $certificate;
    }

    protected function writeFileContent(string $filename, string $content): self
    {
        $splFileObject = new \SplFileObject($filename, 'w');
        if (0 === $splFileObject->fwrite($content)) {
            throw new \RuntimeException('Unable to write file `' . $splFileObject->getRealPath()  . '`.');
        }

        return $this;
    }

    protected function getFileContent(string $filename): string
    {
        $splFileObject = new \SplFileObject($filename, 'r');
        $content = $splFileObject->fread($splFileObject->getSize());
        if (false === $content) {
            throw new \RuntimeException('Unable to read file `' . $splFileObject->getRealPath()  . '`.');
        }

        return $content;
    }
}
