<?php
/**
 * @author Philippe VANDERMOERE <philippe@wizaplace.com>
 * @copyright Copyright (C) Philippe VANDERMOERE
 * @license MIT
 */

declare(strict_types=1);

namespace App\Twig;

use Twig\Extension\ExtensionInterface;
use Twig\Loader\FilesystemLoader;
use Twig\Environment as Twig;

class TwigFactory
{
    /** @var string */
    protected $templatesDirectory;

    /** @var ExtensionInterface[] */
    protected $twigExtensions = [];

    public function __construct(string $templatesDirectory, iterable $twigExtensions = [])
    {
        $this->templatesDirectory = $templatesDirectory;

        foreach ($twigExtensions as $twigExtension) {
            if ($twigExtension instanceof ExtensionInterface) {
                $this->twigExtensions[] = $twigExtension;
            }
        }
    }

    public function create(): Twig
    {
        $twig = new Twig(new FilesystemLoader($this->templatesDirectory));
        foreach ($this->twigExtensions as $twigExtension) {
            $twig->addExtension($twigExtension);
        }

        return $twig;
    }
}
