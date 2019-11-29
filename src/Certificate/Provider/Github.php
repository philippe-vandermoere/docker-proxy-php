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

class Github implements ProviderInterface
{
    protected GithubClient $githubClient;

    public function __construct(GithubClient $githubClient)
    {
        $this->githubClient = $githubClient;
    }

    public function getName(): string
    {
        return 'github';
    }

    public function createCertificate(Certificate $certificate, array $options = []): Certificate
    {
        foreach (['token', 'repository', 'certificate_path', 'private_key_path'] as $key) {
            if (false === \array_key_exists($key, $options)) {
                throw new \InvalidArgumentException('Missing parameter `' . $key . '`.');
            }
        }

        echo 'Certificate: Getting from github for domain `' . $certificate->getDomain() . '`.' . PHP_EOL;

        if (true === \array_key_exists('certificate_chain_path', $options)) {
            $certificate->writeCertificateChain(
                $this->githubClient->getApiContent(
                    $options['repository'],
                    $options['certificate_chain_path'],
                    $options['token'],
                    $options['branch'] ?? 'master'
                )
            );
        }

        return $certificate
            ->writeCertificate(
                $this->githubClient->getApiContent(
                    $options['repository'],
                    $options['certificate_path'],
                    $options['token'],
                    $options['branch'] ?? 'master'
                )
            )
            ->writePrivateKey(
                $this->githubClient->getApiContent(
                    $options['repository'],
                    $options['private_key_path'],
                    $options['token'],
                    $options['branch'] ?? 'master'
                )
            )
        ;
    }
}
