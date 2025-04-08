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

final class Configuration
{
    private string $directory;
    private string $domain;
    private string $email;
    private ?string $apacheConfigurationFile;
    private ?string $nginxConfigurationFile;

    public function __construct(string $directory, string $domain, string $email, ?string $apacheConfigurationFile, ?string $nginxConfigurationFile)
    {
        $this->directory               = $directory;
        $this->domain                  = $domain;
        $this->email                   = $email;
        $this->apacheConfigurationFile = $apacheConfigurationFile;
        $this->nginxConfigurationFile  = $nginxConfigurationFile;
    }

    public function directory(): string
    {
        return $this->directory;
    }

    public function domain(): string
    {
        return $this->domain;
    }

    public function email(): string
    {
        return $this->email;
    }

    /**
     * @phpstan-assert-if-true !null $this->nginxConfigurationFile
     */
    public function shouldGenerateNginxConfigurationFile(): bool
    {
        return $this->nginxConfigurationFile !== null;
    }

    /**
     * @throws RuntimeException
     */
    public function apacheConfigurationFile(): string
    {
        if ($this->apacheConfigurationFile === null) {
            throw new RuntimeException;
        }

        return $this->apacheConfigurationFile;
    }

    /**
     * @phpstan-assert-if-true !null $this->apacheConfigurationFile
     */
    public function shouldGenerateApacheConfigurationFile(): bool
    {
        return $this->apacheConfigurationFile !== null;
    }

    /**
     * @throws RuntimeException
     */
    public function nginxConfigurationFile(): string
    {
        if ($this->nginxConfigurationFile === null) {
            throw new RuntimeException;
        }

        return $this->nginxConfigurationFile;
    }
}
