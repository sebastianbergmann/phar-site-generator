<?php declare(strict_types=1);
/*
 * This file is part of phar-site-generator.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace SebastianBergmann\PharSiteGenerator;

final class Arguments
{
    private ?string $configuration;
    private bool $help;
    private bool $version;

    public function __construct(?string $configuration, bool $help, bool $version)
    {
        $this->configuration = $configuration;
        $this->help          = $help;
        $this->version       = $version;
    }

    public function configuration(): ?string
    {
        return $this->configuration;
    }

    public function help(): bool
    {
        return $this->help;
    }

    public function version(): bool
    {
        return $this->version;
    }
}
