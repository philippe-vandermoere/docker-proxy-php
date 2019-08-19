<?php
/**
 * @author Philippe VANDERMOERE <philippe@wizaplace.com>
 * @copyright Copyright (C) Philippe VANDERMOERE
 * @license MIT
 */

declare(strict_types=1);

namespace App\Certificate;

interface ProviderInterface
{
    public function getName(): string;

    public function createCertificate(Certificate $certificate, array $options = []): Certificate;
}
