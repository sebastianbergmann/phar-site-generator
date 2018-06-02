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
    /**
     * @var string
     */
    private $package;

    /**
     * @var string
     */
    private $version;

    /**
     * @var string
     */
    private $majorVersion;

    /**
     * @var string
     */
    private $minorVersion;

    /**
     * @var string[]
     */
    private $manifest;

    /**
     * @var string
     */
    private $date;

    /**
     * @var string
     */
    private $size;

    /**
     * @var string
     */
    private $sha256;

    public function __construct(string $package, string $version, string $majorVersion, string $minorVersion, array $manifest, string $date, string $size, string $sha256)
    {
        $this->package      = $package;
        $this->version      = $version;
        $this->majorVersion = $majorVersion;
        $this->minorVersion = $minorVersion;
        $this->manifest     = $manifest;
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

    public function manifest(): array
    {
        return $this->manifest;
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
