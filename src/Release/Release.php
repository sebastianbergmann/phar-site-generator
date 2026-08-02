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

final readonly class Release
{
    /**
     * @var non-empty-string
     */
    private string $package;

    /**
     * @var non-empty-string
     */
    private string $version;

    /**
     * @var non-empty-string
     */
    private string $majorVersion;

    /**
     * @var non-empty-string
     */
    private string $minorVersion;

    /**
     * @var non-empty-string
     */
    private string $date;

    /**
     * @var non-empty-string
     */
    private string $size;

    /**
     * @var positive-int
     */
    private int $bytes;

    /**
     * @var non-empty-string
     */
    private string $sha256;

    /**
     * @param non-empty-string $package
     * @param non-empty-string $version
     * @param non-empty-string $majorVersion
     * @param non-empty-string $minorVersion
     * @param non-empty-string $date
     * @param non-empty-string $size
     * @param positive-int     $bytes
     * @param non-empty-string $sha256
     */
    public function __construct(string $package, string $version, string $majorVersion, string $minorVersion, string $date, string $size, int $bytes, string $sha256)
    {
        $this->package      = $package;
        $this->version      = $version;
        $this->majorVersion = $majorVersion;
        $this->minorVersion = $minorVersion;
        $this->date         = $date;
        $this->size         = $size;
        $this->bytes        = $bytes;
        $this->sha256       = $sha256;
    }

    /**
     * @return non-empty-string
     */
    public function asString(): string
    {
        return $this->package . '-' . $this->version;
    }

    /**
     * @return non-empty-string
     */
    public function package(): string
    {
        return $this->package;
    }

    /**
     * @return non-empty-string
     */
    public function version(): string
    {
        return $this->version;
    }

    /**
     * @return non-empty-string
     */
    public function minorVersion(): string
    {
        return $this->minorVersion;
    }

    /**
     * @return non-empty-string
     */
    public function majorVersion(): string
    {
        return $this->majorVersion;
    }

    /**
     * @return non-empty-string
     */
    public function date(): string
    {
        return $this->date;
    }

    /**
     * @return non-empty-string
     */
    public function size(): string
    {
        return $this->size;
    }

    /**
     * @return positive-int
     */
    public function bytes(): int
    {
        return $this->bytes;
    }

    /**
     * @return non-empty-string
     */
    public function sha256(): string
    {
        return $this->sha256;
    }
}
