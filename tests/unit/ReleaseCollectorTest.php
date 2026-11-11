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

use const DIRECTORY_SEPARATOR;
use function assert;
use function bin2hex;
use function copy;
use function file_put_contents;
use function glob;
use function hash;
use function mkdir;
use function random_bytes;
use function rmdir;
use function str_repeat;
use function symlink;
use function sys_get_temp_dir;
use function touch;
use function unlink;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Medium;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[Medium]
#[CoversClass(ReleaseCollector::class)]
#[UsesClass(Cache::class)]
#[UsesClass(PharReader::class)]
#[UsesClass(Release::class)]
#[UsesClass(ReleaseCollection::class)]
final class ReleaseCollectorTest extends TestCase
{
    private const string SHA256_OF_PACKAGE_1_2_3 = 'a72427688cadc3d3a299cc6658839011bdcbb7337493cf903d22c14864791ab1';
    private const string SHA256_OF_PACKAGE_2_3_4 = '6362706920bb68f57582e33d0718284aeb659be8dc7e02d026836529c8fe25d1';
    private string $directory;

    public function testCollectsReleaseForEachPharFileInDirectory(): void
    {
        $this->copyFixture('package-1.2.3.phar');
        $this->copyFixture('package-2.3.4.phar');
        file_put_contents($this->directory . '/README.md', 'not a PHAR file');

        $releases = $this->collect()->allReleases();

        $this->assertCount(2, $releases);
        $this->assertSame('package-1.2.3', $releases[0]->asString());
        $this->assertSame('package-2.3.4', $releases[1]->asString());
    }

    public function testDoesNotCollectReleaseForSymbolicLinkToPharFile(): void
    {
        $this->copyFixture('package-2.3.4.phar');
        symlink($this->directory . '/package-2.3.4.phar', $this->directory . '/package-2.phar');

        $releases = $this->collect()->allReleases();

        $this->assertCount(1, $releases);
        $this->assertSame('package-2.3.4', $releases[0]->asString());
    }

    public function testDerivesPackageAndVersionFromFileName(): void
    {
        $this->copyFixture('package-2.3.4.phar', 'example-package-10.20.30.phar');

        $release = $this->collectOne();

        $this->assertSame('example-package', $release->package());
        $this->assertSame('10.20.30', $release->version());
        $this->assertSame('10', $release->majorVersion());
        $this->assertSame('10.20', $release->minorVersion());
    }

    public function testDerivesTimestampFromModificationTimeOfPharFile(): void
    {
        $this->copyFixture('package-2.3.4.phar');
        touch($this->directory . '/package-2.3.4.phar', 1772323200);

        $this->assertSame(1772323200, $this->collectOne()->timestamp());
    }

    public function testDeterminesSizeAndSha256HashOfPharFile(): void
    {
        $this->copyFixture('package-1.2.3.phar');

        $release = $this->collectOne();

        $this->assertSame(3300, $release->bytes());
        $this->assertSame('3.22K', $release->size());
        $this->assertSame(self::SHA256_OF_PACKAGE_1_2_3, $release->sha256());
    }

    /**
     * @param positive-int     $bytes
     * @param non-empty-string $size
     */
    #[TestWith([1, '1.00B'])]
    #[TestWith([999, '999.00B'])]
    #[TestWith([3300, '3.22K'])]
    #[TestWith([1048576, '1.00M'])]
    public function testDeterminesHumanReadableSizeOfPharFile(int $bytes, string $size): void
    {
        file_put_contents($this->directory . '/package-1.0.0.phar', str_repeat('x', $bytes));

        $this->assertSame($size, $this->collectOne()->size());
    }

    public function testDetectsSbomForPharFile(): void
    {
        $this->copyFixture('package-1.2.3.phar');
        $this->copyFixture('package-2.3.4.phar');
        $this->copyFixture('package-2.3.4.phar.cdx.xml');

        $releases = $this->collect()->allReleases();

        $this->assertCount(2, $releases);
        $this->assertFalse($releases[0]->hasSbom());
        $this->assertTrue($releases[1]->hasSbom());
    }

    public function testReadsBundledDependenciesFromManifest(): void
    {
        $this->copyFixture('package-2.3.4.phar');

        $this->assertSame(
            [
                'example/dependency'         => '1.0.0',
                'example/another-dependency' => '2.0.0@3d8e0fbe5b2fb2e4d36c4d5be6e7ad0b6c3fd2d1',
            ],
            $this->collectOne()->bundledDependencies(),
        );
    }

    public function testReadsBundledDependenciesFromManifestInPharDirectory(): void
    {
        $this->copyFixture('legacy-1.0.0.phar');

        $this->assertSame(['example/dependency' => '0.1.0'], $this->collectOne()->bundledDependencies());
    }

    public function testPharFileWithoutManifestHasNoBundledDependencies(): void
    {
        $this->copyFixture('package-1.2.3.phar');

        $this->assertSame([], $this->collectOne()->bundledDependencies());
    }

    public function testFileThatIsNotPharFileHasNoBundledDependencies(): void
    {
        file_put_contents($this->directory . '/package-1.0.0.phar', 'not a PHAR file');

        $release = $this->collectOne();

        $this->assertSame([], $release->bundledDependencies());
        $this->assertSame(hash('sha256', 'not a PHAR file'), $release->sha256());
    }

    public function testUsesInformationFromCache(): void
    {
        $this->copyFixture('package-2.3.4.phar');
        touch($this->directory . '/package-2.3.4.phar', 1772323200);

        $cache = new Cache(
            [
                'package-2.3.4.phar' => [
                    'bytes'        => 545,
                    'mtime'        => 1772323200,
                    'sha256'       => str_repeat('0', 64),
                    'dependencies' => ['example/cached-dependency' => '3.0.0'],
                ],
            ],
        );

        $release = $this->collectOne($cache);

        $this->assertSame(str_repeat('0', 64), $release->sha256());
        $this->assertSame(['example/cached-dependency' => '3.0.0'], $release->bundledDependencies());
        $this->assertSame(1, $cache->hits());
        $this->assertSame(0, $cache->misses());
    }

    public function testStoresInformationInCache(): void
    {
        $this->copyFixture('package-2.3.4.phar');
        touch($this->directory . '/package-2.3.4.phar', 1772323200);

        $cache = new Cache;

        $this->collect($cache);

        $this->assertSame(0, $cache->hits());
        $this->assertSame(1, $cache->misses());

        $this->assertSame(
            [
                'sha256'       => self::SHA256_OF_PACKAGE_2_3_4,
                'dependencies' => [
                    'example/dependency'         => '1.0.0',
                    'example/another-dependency' => '2.0.0@3d8e0fbe5b2fb2e4d36c4d5be6e7ad0b6c3fd2d1',
                ],
            ],
            $this->storedInformation($cache, 'package-2.3.4.phar', 545, 1772323200),
        );
    }

    #[Before]
    protected function createDirectory(): void
    {
        $this->directory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'phar-site-generator-' . bin2hex(random_bytes(8));

        mkdir($this->directory);
    }

    #[After]
    protected function deleteDirectory(): void
    {
        $files = glob($this->directory . '/*');

        assert($files !== false);

        foreach ($files as $file) {
            unlink($file);
        }

        rmdir($this->directory);
    }

    private function collect(Cache $cache = new Cache): ReleaseCollection
    {
        return (new ReleaseCollector($cache))->collect($this->directory);
    }

    private function collectOne(Cache $cache = new Cache): Release
    {
        $releases = $this->collect($cache)->allReleases();

        $this->assertCount(1, $releases);

        return $releases[0];
    }

    private function copyFixture(string $fixture, ?string $target = null): void
    {
        copy(__DIR__ . '/../fixture/' . $fixture, $this->directory . '/' . ($target ?? $fixture));
    }

    /**
     * Information that was stored in the cache is only available from a
     * cache that is loaded from the file the cache was saved to.
     *
     * @return ?array{sha256: non-empty-string, dependencies: array<non-empty-string, non-empty-string>}
     */
    private function storedInformation(Cache $cache, string $pharFile, int $bytes, int $mtime): ?array
    {
        $cacheFile = $this->directory . '/cache.json';

        $cache->save($cacheFile);

        $information = Cache::fromFile($cacheFile)->lookup($pharFile, $bytes, $mtime);

        unlink($cacheFile);

        return $information;
    }
}
