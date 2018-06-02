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
    /**
     * @var string
     */
    private $directory;

    /**
     * @var string
     */
    private $domain;

    /**
     * @var string
     */
    private $email;

    /**
     * @var string
     */
    private $nginxConfigurationFile;

    /**
     * @var array
     */
    private $additionalReleaseSeries = [];

    public function __construct(string $directory, string $domain, string $email)
    {
        $this->directory = $directory;
        $this->domain    = $domain;
        $this->email     = $email;
    }

    public function setNginxConfigurationFile(string $filename): void
    {
        $this->nginxConfigurationFile = $filename;
    }

    public function addAdditionalReleaseSeries(string $package, string $series, string $alias): void
    {
        $this->additionalReleaseSeries[] = [
            'package' => $package,
            'series'  => $series,
            'alias'   => $alias
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

    public function additionalReleaseSeries(): array
    {
        return $this->additionalReleaseSeries;
    }
}
