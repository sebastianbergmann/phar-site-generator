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
     * @var array
     */
    private $releases = ['all' => []];

    public function add(Release $release)
    {
        $package = $release->package();

        if (!isset($this->releases[$package])) {
            $this->releases[$package] = [
                'latest' => [
                    'all'                    => $release,
                    $release->minorVersion() => $release
                ],
                'all' => []
            ];
        } else {
            if (\version_compare($release->version(), $this->releases[$package]['latest']['all']->version(), '>=')) {
                $this->releases[$package]['latest']['all'] = $release;
            }

            if (!isset($this->releases[$package]['latest'][$release->minorVersion()]) ||
                \version_compare($release->version(), $this->releases[$package]['latest'][$release->minorVersion()]->version(), '>=')) {
                $this->releases[$package]['latest'][$release->minorVersion()] = $release;
            }
        }

        $this->releases[$package]['all'][] = $release;
        $this->releases['all'][]           = $release;
    }

    /**
     * @return Release[]
     */
    public function allReleases()
    {
        return $this->releases['all'];
    }

    /**
     * @return Release[]
     */
    public function latestReleases()
    {
        $latest = [];

        foreach ($this->releases as $package => $releases) {
            if ($package === 'all') {
                continue;
            }

            $latest[] = $releases['latest']['all'];
        }

        return $latest;
    }

    /**
     * @return Release[]
     */
    public function latestReleasesPerPackageAndMinorVersion()
    {
        $latest = [];

        foreach ($this->releases as $package => $releases) {
            if ($package === 'all') {
                continue;
            }

            foreach (\array_keys($this->releases[$package]['latest']) as $minorVersion) {
                if ($minorVersion === 'all') {
                    continue;
                }

                $latest[] = $this->latestReleaseOfMinorVersion($package, $minorVersion);
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
        return $this->releases[$package]['latest'][$minorVersion];
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
}
