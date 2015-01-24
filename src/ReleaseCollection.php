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

    /**
     * @param Release $release
     */
    public function add(Release $release)
    {
        $package = $release->package();

        if (!isset($this->releases[$package])) {
            $this->releases[$package] = [
                'latest' => $release,
                'all'    => []
            ];
        } else {
            if (version_compare($release->version(), $this->releases[$package]['latest']->version(), '>=')) {
                $this->releases[$package]['latest'] = $release;
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
            if ($package == 'all') {
                continue;
            }

            $latest[] = $releases['latest'];
        }

        return $latest;
    }

    /**
     * @return Release[]
     */
    public function latestReleasesSortedByDate()
    {
        $latest = $this->latestReleases();

        usort(
            $latest,
            function ($a, $b) {
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

        usort(
            $latest,
            function ($a, $b) {
                return $a->package() >= $b->package();
            }
        );

        return $latest;
    }
}
