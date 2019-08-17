<?php
/**
 * @author Philippe VANDERMOERE <philippe@wizaplace.com>
 * @copyright Copyright (C) Philippe VANDERMOERE
 * @license MIT
 */

declare(strict_types=1);

namespace App\Certificate\Provider;

use App\Certificate\Certificate;
use App\Certificate\ProviderInterface;

class SelfSigned implements ProviderInterface
{
    protected const PRIVATE_KEY_LENGTH = 2048;

    protected const CERTIFICATE_VALIDITY_DAYS = 365;

    public function getName(): string
    {
        return 'self-signed';
    }

    public function createCertificate(Certificate $certificate, array $options = []): Certificate
    {
        echo 'Certificate: Created self signed for domain `' . $certificate->getDomain() . '`' . PHP_EOL;
        $key = \openssl_pkey_new(
            [
                'private_key_bits' => static::PRIVATE_KEY_LENGTH,
                'private_key_type' => OPENSSL_KEYTYPE_RSA
            ]
        );

        // @codeCoverageIgnoreStart
        if (false === $key) {
            throw new \RuntimeException('Unable to create privateKey.');
        }

        $csr = \openssl_csr_new(
            [
                'commonName' => $certificate->getDomain()
            ],
            $key
        );

        // @codeCoverageIgnoreStart
        if (false === $csr) {
            throw new \RuntimeException('Unable to create CSR.');
        }
        // @codeCoverageIgnoreEnd

        $csrSign = \openssl_csr_sign($csr, null, $key, static::CERTIFICATE_VALIDITY_DAYS);

        // @codeCoverageIgnoreStart
        if (false === $csrSign) {
            throw new \RuntimeException('Unable to sign CSR with privateKey.');
        }

        if (false === \openssl_x509_export($csrSign, $cert)) {
            throw new \RuntimeException('Unable to export certificate.');
        }

        if (false === \openssl_pkey_export($key, $privateKey)) {
            throw new \RuntimeException('Unable to export privateKey.');
        }
        // @codeCoverageIgnoreEnd

        return $certificate
            ->writeCertificate($cert)
            ->writePrivateKey($privateKey)
        ;
    }
}
