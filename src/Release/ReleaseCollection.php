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

class ReleaseCollection
{
    /**
     * @var Release[]
     */
    private $all = [];

    /**
     * @var array
     */
    private $latestVersion = [];

    /**
     * @var array
     */
    private $latestMinorVersion = [];

    /**
     * @var array
     */
    private $latestMajorVersion = [];

    public function add(Release $release)
    {
        $package      = $release->package();
        $minorVersion = $release->minorVersion();
        $majorVersion = $release->majorVersion();

        if (!isset($this->latestVersion[$package])) {
            $this->latestVersion[$package] = $release;
        } else {
            if (\version_compare($release->version(), $this->latestVersion[$package]->version(), '>=')) {
                $this->latestVersion[$package] = $release;
            }
        }

        if (!isset($this->latestMajorVersion[$package])) {
            $this->latestMajorVersion[$package] = [$majorVersion => $release];
        } elseif (!isset($this->latestMajorVersion[$package][$majorVersion])) {
            $this->latestMajorVersion[$package][$majorVersion] = $release;
        } elseif (\version_compare($release->version(), $this->latestMajorVersion[$package][$majorVersion]->version(), '>=')) {
            $this->latestMajorVersion[$package][$majorVersion] = $release;
        }

        if (!isset($this->latestMinorVersion[$package])) {
            $this->latestMinorVersion[$package] = [$minorVersion => $release];
        } elseif (!isset($this->latestMinorVersion[$package][$minorVersion])) {
            $this->latestMinorVersion[$package][$minorVersion] = $release;
        } elseif (\version_compare($release->version(), $this->latestMinorVersion[$package][$minorVersion]->version(), '>=')) {
            $this->latestMinorVersion[$package][$minorVersion] = $release;
        }

        $this->all[] = $release;
    }

    /**
     * @return Release[]
     */
    public function allReleases()
    {
        return $this->all;
    }

    /**
     * @return Release[]
     */
    public function latestReleases()
    {
        return $this->latestVersion;
    }

    /**
     * @return Release[]
     */
    public function latestReleasesPerPackageAndMajorVersion()
    {
        $latest = [];

        foreach ($this->packages() as $package) {
            foreach ($this->latestMajorVersion[$package] as $release) {
                $latest[] = $release;
            }
        }

        return $latest;
    }

    /**
     * @return Release[]
     */
    public function latestReleasesPerPackageAndMinorVersion()
    {
        $latest = [];

        foreach ($this->packages() as $package) {
            foreach ($this->latestMinorVersion[$package] as $release) {
                $latest[] = $release;
            }
        }

        return $latest;
    }

    /**
     * @param string $package
     * @param string $minorVersion
     *
     * @return Release
     */
    public function latestReleaseOfMinorVersion($package, $minorVersion)
    {
        return $this->latestMinorVersion[$package][$minorVersion];
    }

    /**
     * @return Release[]
     */
    public function latestReleasesSortedByDate()
    {
        $latest = $this->latestReleases();

        \usort(
            $latest,
            function (Release $a, Release $b) {
                return $a->date() <= $b->date();
            }
        );

        return $latest;
    }

    /**
     * @return Release[]
     */
    public function latestReleasesSortedByPackageName()
    {
        $latest = $this->latestReleases();

        \usort(
            $latest,
            function (Release $a, Release $b) {
                return $a->package() >= $b->package();
            }
        );

        return $latest;
    }

    public function packages()
    {
        return array_keys($this->latestVersion);
    }
}
