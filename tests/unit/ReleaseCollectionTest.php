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

use function array_map;
use function str_repeat;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[Small]
#[CoversClass(ReleaseCollection::class)]
#[UsesClass(Release::class)]
final class ReleaseCollectionTest extends TestCase
{
    public function testIsInitiallyEmpty(): void
    {
        $releases = new ReleaseCollection;

        $this->assertSame([], $releases->allReleases());
        $this->assertSame([], $releases->latestReleases());
        $this->assertSame([], $releases->latestReleasesPerPackageAndMajorVersion());
        $this->assertSame([], $releases->latestReleasesPerPackageAndMinorVersion());
        $this->assertSame([], $releases->packages());
    }

    public function testListsAllReleasesInNaturalOrder(): void
    {
        $releases = $this->releases(
            $this->release('package', '1.10.0', '1', '1.10'),
            $this->release('other', '1.0.0', '1', '1.0'),
            $this->release('package', '1.9.0', '1', '1.9'),
            $this->release('package', '1.2.0', '1', '1.2'),
        );

        $this->assertSame(
            ['other-1.0.0', 'package-1.2.0', 'package-1.9.0', 'package-1.10.0'],
            $this->asStrings($releases->allReleases()),
        );
    }

    public function testListsAllReleasesInNaturalOrderWhenReleasesAreAddedAfterTheyWereListed(): void
    {
        $releases = $this->releases(
            $this->release('package', '1.10.0', '1', '1.10'),
        );

        $this->assertSame(['package-1.10.0'], $this->asStrings($releases->allReleases()));

        $releases->add($this->release('package', '1.9.0', '1', '1.9'));

        $this->assertSame(['package-1.9.0', 'package-1.10.0'], $this->asStrings($releases->allReleases()));
        $this->assertSame(['package-1.9.0', 'package-1.10.0'], $this->asStrings($releases->allReleases()));
    }

    public function testListsLatestReleaseOfEachPackage(): void
    {
        $releases = $this->releases(
            $this->release('package', '1.0.0', '1', '1.0'),
            $this->release('package', '2.0.0', '2', '2.0'),
            $this->release('package', '1.5.0', '1', '1.5'),
            $this->release('other', '3.0.0', '3', '3.0'),
        );

        $this->assertSame(
            ['package' => 'package-2.0.0', 'other' => 'other-3.0.0'],
            $this->asStrings($releases->latestReleases()),
        );
    }

    public function testListsLatestReleaseOfEachMajorVersionOfEachPackage(): void
    {
        $releases = $this->releases(
            $this->release('package', '1.0.0', '1', '1.0'),
            $this->release('package', '2.0.0', '2', '2.0'),
            $this->release('package', '1.5.0', '1', '1.5'),
            $this->release('package', '1.4.0', '1', '1.4'),
            $this->release('other', '3.0.0', '3', '3.0'),
        );

        $this->assertSame(
            ['package-1.5.0', 'package-2.0.0', 'other-3.0.0'],
            $this->asStrings($releases->latestReleasesPerPackageAndMajorVersion()),
        );
    }

    public function testListsLatestReleaseOfEachMinorVersionOfEachPackage(): void
    {
        $releases = $this->releases(
            $this->release('package', '1.0.0', '1', '1.0'),
            $this->release('package', '1.1.0', '1', '1.1'),
            $this->release('package', '1.0.2', '1', '1.0'),
            $this->release('package', '1.0.1', '1', '1.0'),
            $this->release('other', '3.0.0', '3', '3.0'),
        );

        $this->assertSame(
            ['package-1.0.2', 'package-1.1.0', 'other-3.0.0'],
            $this->asStrings($releases->latestReleasesPerPackageAndMinorVersion()),
        );
    }

    public function testListsLatestReleasesSortedByDate(): void
    {
        $releases = $this->releases(
            $this->release('a', '1.0.0', '1', '1.0', 300),
            $this->release('b', '1.0.0', '1', '1.0', 100),
            $this->release('c', '1.0.0', '1', '1.0', 200),
        );

        $this->assertSame(
            ['b-1.0.0', 'c-1.0.0', 'a-1.0.0'],
            $this->asStrings($releases->latestReleasesSortedByDate()),
        );
    }

    public function testListsLatestReleasesSortedByDateInDescendingOrder(): void
    {
        $releases = $this->releases(
            $this->release('a', '1.0.0', '1', '1.0', 100),
            $this->release('b', '1.0.0', '1', '1.0', 300),
            $this->release('c', '1.0.0', '1', '1.0', 200),
        );

        $this->assertSame(
            ['b-1.0.0', 'c-1.0.0', 'a-1.0.0'],
            $this->asStrings($releases->latestReleasesSortedByDateDescending()),
        );
    }

    public function testListsLatestReleasesWithSameDateSortedByPackageName(): void
    {
        $releases = $this->releases(
            $this->release('c', '1.0.0', '1', '1.0', 100),
            $this->release('a', '1.0.0', '1', '1.0', 100),
            $this->release('d', '1.0.0', '1', '1.0', 200),
            $this->release('b', '1.0.0', '1', '1.0', 100),
        );

        $this->assertSame(
            ['d-1.0.0', 'a-1.0.0', 'b-1.0.0', 'c-1.0.0'],
            $this->asStrings($releases->latestReleasesSortedByDateDescending()),
        );
    }

    public function testListsLatestReleasesSortedByPackageName(): void
    {
        $releases = $this->releases(
            $this->release('c', '1.0.0', '1', '1.0'),
            $this->release('a', '1.0.0', '1', '1.0'),
            $this->release('b', '1.0.0', '1', '1.0'),
        );

        $this->assertSame(
            ['a-1.0.0', 'b-1.0.0', 'c-1.0.0'],
            $this->asStrings($releases->latestReleasesSortedByPackageName()),
        );
    }

    public function testListsPackages(): void
    {
        $releases = $this->releases(
            $this->release('package', '1.0.0', '1', '1.0'),
            $this->release('other', '1.0.0', '1', '1.0'),
            $this->release('package', '2.0.0', '2', '2.0'),
        );

        $this->assertSame(['package', 'other'], $releases->packages());
    }

    private function releases(Release ...$releases): ReleaseCollection
    {
        $collection = new ReleaseCollection;

        foreach ($releases as $release) {
            $collection->add($release);
        }

        return $collection;
    }

    /**
     * @param non-empty-string $package
     * @param non-empty-string $version
     * @param non-empty-string $majorVersion
     * @param non-empty-string $minorVersion
     */
    private function release(string $package, string $version, string $majorVersion, string $minorVersion, int $timestamp = 0): Release
    {
        return new Release(
            $package,
            $version,
            $majorVersion,
            $minorVersion,
            $timestamp,
            '1.00K',
            1024,
            str_repeat('a', 64),
            false,
            [],
        );
    }

    /**
     * @template TKey of array-key
     *
     * @param array<TKey, Release> $releases
     *
     * @return array<TKey, string>
     */
    private function asStrings(array $releases): array
    {
        return array_map(
            static fn (Release $release): string => $release->asString(),
            $releases,
        );
    }
}
