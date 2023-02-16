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
    private ?string $nginxConfigurationFile;

    /**
     * @psalm-var list<array{package: string, series: string, alias: string}>
     */
    private array $additionalReleaseSeries = [];

    public function __construct(string $directory, string $domain, string $email, ?string $nginxConfigurationFile)
    {
        $this->directory              = $directory;
        $this->domain                 = $domain;
        $this->email                  = $email;
        $this->nginxConfigurationFile = $nginxConfigurationFile;
    }

    public function addAdditionalReleaseSeries(string $package, string $series, string $alias): void
    {
        $this->additionalReleaseSeries[] = [
            'package' => $package,
            'series'  => $series,
            'alias'   => $alias,
        ];
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

    public function shouldGenerateNginxConfigurationFile(): bool
    {
        return $this->nginxConfigurationFile !== null;
    }

    public function nginxConfigurationFile(): string
    {
        return $this->nginxConfigurationFile;
    }

    /**
     * @psalm-return list<array{package: string, series: string, alias: string}>
     */
    public function additionalReleaseSeries(): array
    {
        return $this->additionalReleaseSeries;
    }
}
