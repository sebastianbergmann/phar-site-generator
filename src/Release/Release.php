<?php
/*
 * This file is part of phar-site-generator.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SebastianBergmann\PharSiteGenerator;

class Release
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

    /**
     * @param string $package
     * @param string $version
     * @param string $majorVersion
     * @param string $minorVersion
     * @param array  $manifest
     * @param string $date
     * @param string $size
     * @param string $sha256
     */
    public function __construct($package, $version, $majorVersion, $minorVersion, array $manifest, $date, $size, $sha256)
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

    /**
     * @return string
     */
    public function package()
    {
        return $this->package;
    }

    /**
     * @return string
     */
    public function version()
    {
        return $this->version;
    }

    /**
     * @return string
     */
    public function minorVersion()
    {
        return $this->minorVersion;
    }

    /**
     * @return string
     */
    public function majorVersion()
    {
        return $this->majorVersion;
    }

    /**
     * @return string[]
     */
    public function manifest()
    {
        return $this->manifest;
    }

    /**
     * @return string
     */
    public function date()
    {
        return $this->date;
    }

    /**
     * @return string
     */
    public function size()
    {
        return $this->size;
    }

    /**
     * @return string
     */
    public function sha256()
    {
        return $this->sha256;
    }
}
