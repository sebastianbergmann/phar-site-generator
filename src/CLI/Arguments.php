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

final readonly class Arguments
{
    /**
     * @var ?non-empty-string
     */
    private ?string $configurationFile;
    private bool $help;
    private bool $version;

    /**
     * @param ?non-empty-string $configurationFile
     */
    public function __construct(?string $configurationFile, bool $help, bool $version)
    {
        $this->configurationFile = $configurationFile;
        $this->help              = $help;
        $this->version           = $version;
    }

    /**
     * @phpstan-assert-if-true !null $this->configurationFile
     */
    public function hasConfigurationFile(): bool
    {
        return $this->configurationFile !== null;
    }

    public function configurationFile(): string
    {
        if ($this->configurationFile === null) {
            throw new RuntimeException;
        }

        return $this->configurationFile;
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
