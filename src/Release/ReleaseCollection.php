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

use function array_keys;
use function assert;
use function strnatcmp;
use function usort;
use function version_compare;

final class ReleaseCollection
{
    /**
     * @psalm-var list<Release>
     */
    private array $all = [];

    /**
     * @psalm-var array<non-empty-string, Release>
     */
    private array $latestVersion = [];

    /**
     * @psalm-var array<non-empty-string, array<non-empty-string, Release>>
     */
    private array $latestMinorVersion = [];

    /**
     * @psalm-var array<non-empty-string, array<non-empty-string, Release>>
     */
    private array $latestMajorVersion = [];
    private bool $sorted              = false;

    public function add(Release $release): void
    {
        $package      = $release->package();
        $minorVersion = $release->minorVersion();
        $majorVersion = $release->majorVersion();

        if (!isset($this->latestVersion[$package])) {
            $this->latestVersion[$package] = $release;
        } else {
            if (version_compare($release->version(), $this->latestVersion[$package]->version(), '>=')) {
                $this->latestVersion[$package] = $release;
            }
        }

        if (!isset($this->latestMajorVersion[$package])) {
            $this->latestMajorVersion[$package] = [$majorVersion => $release];
        } elseif (!isset($this->latestMajorVersion[$package][$majorVersion])) {
            $this->latestMajorVersion[$package][$majorVersion] = $release;
        } elseif (version_compare($release->version(), $this->latestMajorVersion[$package][$majorVersion]->version(), '>=')) {
            $this->latestMajorVersion[$package][$majorVersion] = $release;
        }

        if (!isset($this->latestMinorVersion[$package])) {
            $this->latestMinorVersion[$package] = [$minorVersion => $release];
        } elseif (!isset($this->latestMinorVersion[$package][$minorVersion])) {
            $this->latestMinorVersion[$package][$minorVersion] = $release;
        } elseif (version_compare($release->version(), $this->latestMinorVersion[$package][$minorVersion]->version(), '>=')) {
            $this->latestMinorVersion[$package][$minorVersion] = $release;
        }

        $this->all[] = $release;

        $this->sorted = false;
    }

    /**
     * @psalm-return list<Release>
     */
    public function allReleases(): array
    {
        $this->sort();

        return $this->all;
    }

    /**
     * @psalm-return array<string, Release>
     */
    public function latestReleases(): array
    {
        return $this->latestVersion;
    }

    /**
     * @psalm-return list<Release>
     */
    public function latestReleasesPerPackageAndMajorVersion(): array
    {
        $latest = [];

        foreach ($this->packages() as $package) {
            assert(isset($this->latestMajorVersion[$package]));

            foreach ($this->latestMajorVersion[$package] as $release) {
                $latest[] = $release;
            }
        }

        return $latest;
    }

    /**
     * @psalm-return list<Release>
     */
    public function latestReleasesPerPackageAndMinorVersion(): array
    {
        $latest = [];

        foreach ($this->packages() as $package) {
            assert(isset($this->latestMinorVersion[$package]));

            foreach ($this->latestMinorVersion[$package] as $release) {
                $latest[] = $release;
            }
        }

        return $latest;
    }

    /**
     * @psalm-return list<Release>
     */
    public function latestReleasesSortedByDate(): array
    {
        $latest = $this->latestReleases();

        usort(
            $latest,
            static function (Release $a, Release $b): int
            {
                return $a->date() <=> $b->date();
            },
        );

        return $latest;
    }

    /**
     * @psalm-return list<Release>
     */
    public function latestReleasesSortedByPackageName(): array
    {
        $latest = $this->latestReleases();

        usort(
            $latest,
            static function (Release $a, Release $b): int
            {
                return $a->package() <=> $b->package();
            },
        );

        return $latest;
    }

    /**
     * @psalm-return list<string>
     */
    public function packages(): array
    {
        return array_keys($this->latestVersion);
    }

    private function sort(): void
    {
        if ($this->sorted) {
            return;
        }

        usort(
            $this->all,
            static fn (Release $a, Release $b): int => strnatcmp($a->asString(), $b->asString()),
        );

        $this->sorted = true;
    }
}
