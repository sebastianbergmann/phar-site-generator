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

final class Release
{
    private string $package;
    private string $version;
    private string $majorVersion;
    private string $minorVersion;
    private string $date;
    private string $size;
    private string $sha256;

    public function __construct(string $package, string $version, string $majorVersion, string $minorVersion, string $date, string $size, string $sha256)
    {
        $this->package      = $package;
        $this->version      = $version;
        $this->majorVersion = $majorVersion;
        $this->minorVersion = $minorVersion;
        $this->date         = $date;
        $this->size         = $size;
        $this->sha256       = $sha256;
    }

    public function package(): string
    {
        return $this->package;
    }

    public function version(): string
    {
        return $this->version;
    }

    public function minorVersion(): string
    {
        return $this->minorVersion;
    }

    public function majorVersion(): string
    {
        return $this->majorVersion;
    }

    public function date(): string
    {
        return $this->date;
    }

    public function size(): string
    {
        return $this->size;
    }

    public function sha256(): string
    {
        return $this->sha256;
    }
}
