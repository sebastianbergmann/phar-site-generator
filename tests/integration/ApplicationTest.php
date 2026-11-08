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

use const JSON_THROW_ON_ERROR;
use function array_keys;
use function assert;
use function copy;
use function file_get_contents;
use function file_put_contents;
use function filemtime;
use function glob;
use function is_dir;
use function is_file;
use function json_decode;
use function json_encode;
use function mkdir;
use function rmdir;
use function rtrim;
use function str_repeat;
use function unlink;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\Large;
use PHPUnit\Framework\TestCase;

#[Large]
final class ApplicationTest extends TestCase
{
    private const string SHA256_OF_PACKAGE_1_2_3 = 'a72427688cadc3d3a299cc6658839011bdcbb7337493cf903d22c14864791ab1';
    private const string SHA256_OF_PACKAGE_2_3_4 = '6362706920bb68f57582e33d0718284aeb659be8dc7e02d026836529c8fe25d1';
    private const int BYTES_OF_PACKAGE_2_3_4     = 545;

    public function testGeneratesSite(): void
    {
        $this->assertSame('2 releases processed, 0 cache hits, 2 cache misses', $this->generate());

        $this->assertFileEquals(__DIR__ . '/../../src/assets/css/style.css', '/tmp/phar.example.org/public/css/style.css');
        $this->assertFileEquals(__DIR__ . '/../../src/assets/fonts/OpenSans.ttf', '/tmp/phar.example.org/public/fonts/OpenSans.ttf');
        $this->assertFileEquals(__DIR__ . '/../../src/assets/fonts/SourceCodePro.ttf', '/tmp/phar.example.org/public/fonts/SourceCodePro.ttf');
        $this->assertFileMatchesFormatFile(__DIR__ . '/../expectations/public/index.html', '/tmp/phar.example.org/public/index.html');
        $this->assertFileEquals(__DIR__ . '/../expectations/apache-redirects.conf', '/tmp/phar.example.org/apache-redirects.conf');
        $this->assertFileEquals(__DIR__ . '/../expectations/nginx-redirects.conf', '/tmp/phar.example.org/nginx-redirects.conf');
        $this->assertFileEquals(__DIR__ . '/../expectations/public/latest-version-of/package', '/tmp/phar.example.org/public/latest-version-of/package');
        $this->assertFileEquals(__DIR__ . '/../expectations/public/latest-version-of/package-1', '/tmp/phar.example.org/public/latest-version-of/package-1');
        $this->assertFileEquals(__DIR__ . '/../expectations/public/latest-version-of/package-1.2', '/tmp/phar.example.org/public/latest-version-of/package-1.2');
        $this->assertFileEquals(__DIR__ . '/../expectations/public/latest-version-of/package-2', '/tmp/phar.example.org/public/latest-version-of/package-2');
        $this->assertFileEquals(__DIR__ . '/../expectations/public/latest-version-of/package-2.3', '/tmp/phar.example.org/public/latest-version-of/package-2.3');
        $this->assertFileEquals(__DIR__ . '/../expectations/public/phive.xml', '/tmp/phar.example.org/public/phive.xml');
        $this->assertFileMatchesFormatFile(__DIR__ . '/../expectations/public/releases.rss', '/tmp/phar.example.org/public/releases.rss');
    }

    public function testPrintsSummary(): void
    {
        $this->expectOutputRegex('/^2 releases processed, 0 cache hits, 2 cache misses$/m');

        $this->assertSame(0, (new Application)->run(['phar-site-generator', __DIR__ . '/../fixture/configuration.xml']));
    }

    public function testDoesNotReportCacheHitsAndMissesWhenCacheIsNotConfigured(): void
    {
        $this->assertSame('2 releases processed', $this->generate(__DIR__ . '/../fixture/configuration-without-cache.xml'));
        $this->assertFileDoesNotExist('/tmp/phar.example.org/cache.json');
    }

    public function testDoesNotListIgnoredPackagesInLatestReleases(): void
    {
        $this->assertSame('2 releases processed', $this->generate(__DIR__ . '/../fixture/configuration-with-ignored-package.xml'));

        $this->assertFileMatchesFormatFile(__DIR__ . '/../expectations/index-with-ignored-package.html', '/tmp/phar.example.org/public/index.html');
        $this->assertFileEquals(__DIR__ . '/../expectations/public/latest-version-of/package', '/tmp/phar.example.org/public/latest-version-of/package');
        $this->assertFileEquals(__DIR__ . '/../expectations/public/phive.xml', '/tmp/phar.example.org/public/phive.xml');
        $this->assertFileMatchesFormatFile(__DIR__ . '/../expectations/public/releases.rss', '/tmp/phar.example.org/public/releases.rss');
    }

    public function testReportsOnlyCacheHitsWhenNoPharFileWasAdded(): void
    {
        $this->generate();

        $this->assertSame('2 releases processed, 2 cache hits, 0 cache misses', $this->generate());
    }

    public function testWritesCache(): void
    {
        $this->generate();

        $this->assertSame(
            [
                'package-1.2.3.phar' => [
                    'bytes'        => 3300,
                    'mtime'        => $this->mtime('package-1.2.3.phar'),
                    'sha256'       => self::SHA256_OF_PACKAGE_1_2_3,
                    'dependencies' => [],
                ],
                'package-2.3.4.phar' => [
                    'bytes'        => self::BYTES_OF_PACKAGE_2_3_4,
                    'mtime'        => $this->mtime('package-2.3.4.phar'),
                    'sha256'       => self::SHA256_OF_PACKAGE_2_3_4,
                    'dependencies' => [
                        'example/dependency'         => '1.0.0',
                        'example/another-dependency' => '2.0.0@3d8e0fbe5b2fb2e4d36c4d5be6e7ad0b6c3fd2d1',
                    ],
                ],
            ],
            $this->readCache(),
        );
    }

    public function testUsesCachedInformation(): void
    {
        $cached = str_repeat('0', 64);

        $this->writeCache(
            [
                'package-2.3.4.phar' => [
                    'bytes'        => self::BYTES_OF_PACKAGE_2_3_4,
                    'mtime'        => $this->mtime('package-2.3.4.phar'),
                    'sha256'       => $cached,
                    'dependencies' => ['example/cached-dependency' => '3.0.0'],
                ],
            ],
        );

        $this->assertSame('2 releases processed, 1 cache hit, 1 cache miss', $this->generate());

        $phive = file_get_contents('/tmp/phar.example.org/public/phive.xml');

        $this->assertIsString($phive);
        $this->assertStringContainsString('value="' . $cached . '"', $phive);
        $this->assertStringContainsString('value="' . self::SHA256_OF_PACKAGE_1_2_3 . '"', $phive);
        $this->assertSame($cached, $this->cachedHash('package-2.3.4.phar'));
        $this->assertSame(['example/cached-dependency' => '3.0.0'], $this->cachedDependencies('package-2.3.4.phar'));
    }

    public function testDoesNotUseCachedInformationWhenModificationTimeDiffers(): void
    {
        $this->writeCache(
            [
                'package-2.3.4.phar' => [
                    'bytes'        => self::BYTES_OF_PACKAGE_2_3_4,
                    'mtime'        => $this->mtime('package-2.3.4.phar') - 1,
                    'sha256'       => str_repeat('0', 64),
                    'dependencies' => [],
                ],
            ],
        );

        $this->generate();

        $this->assertFileEquals(__DIR__ . '/../expectations/public/phive.xml', '/tmp/phar.example.org/public/phive.xml');
        $this->assertSame(self::SHA256_OF_PACKAGE_2_3_4, $this->cachedHash('package-2.3.4.phar'));
        $this->assertCount(2, $this->cachedDependencies('package-2.3.4.phar'));
    }

    public function testDoesNotUseCachedInformationWhenSizeDiffers(): void
    {
        $this->writeCache(
            [
                'package-2.3.4.phar' => [
                    'bytes'        => self::BYTES_OF_PACKAGE_2_3_4 + 1,
                    'mtime'        => $this->mtime('package-2.3.4.phar'),
                    'sha256'       => str_repeat('0', 64),
                    'dependencies' => [],
                ],
            ],
        );

        $this->generate();

        $this->assertFileEquals(__DIR__ . '/../expectations/public/phive.xml', '/tmp/phar.example.org/public/phive.xml');
        $this->assertSame(self::SHA256_OF_PACKAGE_2_3_4, $this->cachedHash('package-2.3.4.phar'));
        $this->assertCount(2, $this->cachedDependencies('package-2.3.4.phar'));
    }

    /**
     * Cache files written by phar-site-generator 5.4 to 5.6 only contain the
     * SHA-256 hash of each PHAR file, but not the dependencies bundled in it.
     */
    public function testDoesNotUseCachedInformationWithoutBundledDependencies(): void
    {
        $this->writeCache(
            [
                'package-2.3.4.phar' => [
                    'bytes'  => self::BYTES_OF_PACKAGE_2_3_4,
                    'mtime'  => $this->mtime('package-2.3.4.phar'),
                    'sha256' => str_repeat('0', 64),
                ],
            ],
        );

        $this->assertSame('2 releases processed, 0 cache hits, 2 cache misses', $this->generate());
        $this->assertFileEquals(__DIR__ . '/../expectations/public/phive.xml', '/tmp/phar.example.org/public/phive.xml');
        $this->assertSame(self::SHA256_OF_PACKAGE_2_3_4, $this->cachedHash('package-2.3.4.phar'));
        $this->assertCount(2, $this->cachedDependencies('package-2.3.4.phar'));
    }

    public function testRemovesPharFilesThatNoLongerExistFromCache(): void
    {
        $this->writeCache(
            [
                'package-0.1.0.phar' => [
                    'bytes'        => 3300,
                    'mtime'        => 0,
                    'sha256'       => str_repeat('0', 64),
                    'dependencies' => [],
                ],
            ],
        );

        $this->generate();

        $this->assertSame(['package-1.2.3.phar', 'package-2.3.4.phar'], array_keys($this->readCache()));
    }

    public function testIgnoresMalformedCache(): void
    {
        file_put_contents('/tmp/phar.example.org/cache.json', '{"package-2.3.4.phar": ');

        $this->generate();

        $this->assertFileEquals(__DIR__ . '/../expectations/public/phive.xml', '/tmp/phar.example.org/public/phive.xml');
        $this->assertSame(self::SHA256_OF_PACKAGE_2_3_4, $this->cachedHash('package-2.3.4.phar'));
    }

    /**
     * Older PHARs, phpcov 2.0 for instance, have their manifest in a phar/ directory.
     */
    public function testReadsBundledDependenciesFromManifestInPharDirectory(): void
    {
        copy(__DIR__ . '/../fixture/legacy-1.0.0.phar', '/tmp/phar.example.org/public/legacy-1.0.0.phar');

        $this->assertSame('3 releases processed, 0 cache hits, 3 cache misses', $this->generate());
        $this->assertSame(['example/dependency' => '0.1.0'], $this->cachedDependencies('legacy-1.0.0.phar'));
    }

    public function testFailsWhenCacheCannotBeWritten(): void
    {
        $this->createDirectory('/tmp/phar.example.org/cache.json.tmp');

        $this->expectOutputRegex('#Cache file "/tmp/phar\.example\.org/cache\.json" could not be written: Failed to open stream: Is a directory#');

        $this->assertSame(1, (new Application)->run(['phar-site-generator', __DIR__ . '/../fixture/configuration.xml']));
        $this->assertFileDoesNotExist('/tmp/phar.example.org/public/index.html');
    }

    public function testFailsWhenCacheCannotBeReplaced(): void
    {
        $this->createDirectory('/tmp/phar.example.org/cache.json');

        $this->expectOutputRegex('#Cache file "/tmp/phar\.example\.org/cache\.json" could not be written: Is a directory#');

        $this->assertSame(1, (new Application)->run(['phar-site-generator', __DIR__ . '/../fixture/configuration.xml']));
        $this->assertFileDoesNotExist('/tmp/phar.example.org/cache.json.tmp');
    }

    #[Before(2)]
    #[After]
    protected function cleanUp(): void
    {
        $this->deleteDirectory('/tmp/phar.example.org');
    }

    #[Before(1)]
    protected function copyFixture(): void
    {
        $this->createDirectory('/tmp/phar.example.org/public');

        copy(__DIR__ . '/../fixture/package-1.2.3.phar', '/tmp/phar.example.org/public/package-1.2.3.phar');
        copy(__DIR__ . '/../fixture/package-1.2.3.phar.asc', '/tmp/phar.example.org/public/package-1.2.3.phar.asc');
        copy(__DIR__ . '/../fixture/package-2.3.4.phar', '/tmp/phar.example.org/public/package-2.3.4.phar');
        copy(__DIR__ . '/../fixture/package-2.3.4.phar.asc', '/tmp/phar.example.org/public/package-2.3.4.phar.asc');
        copy(__DIR__ . '/../fixture/package-2.3.4.phar.cdx.xml', '/tmp/phar.example.org/public/package-2.3.4.phar.cdx.xml');
        copy(__DIR__ . '/../fixture/package-2.3.4.phar.cdx.xml.asc', '/tmp/phar.example.org/public/package-2.3.4.phar.cdx.xml.asc');
    }

    /**
     * @param non-empty-string $configurationFile
     */
    private function generate(string $configurationFile = __DIR__ . '/../fixture/configuration.xml'): string
    {
        return (new Application)->generate(
            new Arguments(
                $configurationFile,
                false,
                false,
            ),
        );
    }

    private function mtime(string $file): int
    {
        $mtime = filemtime('/tmp/phar.example.org/public/' . $file);

        assert($mtime !== false);

        return $mtime;
    }

    /**
     * @param array<string, array<string, mixed>> $entries
     */
    private function writeCache(array $entries): void
    {
        file_put_contents('/tmp/phar.example.org/cache.json', json_encode($entries, JSON_THROW_ON_ERROR));
    }

    /**
     * @return array<string, array{bytes: int, mtime: int, sha256: string, dependencies: array<string, string>}>
     */
    private function readCache(): array
    {
        $buffer = file_get_contents('/tmp/phar.example.org/cache.json');

        assert($buffer !== false);

        /** @var array<string, array{bytes: int, mtime: int, sha256: string, dependencies: array<string, string>}> */
        return json_decode($buffer, true, flags: JSON_THROW_ON_ERROR);
    }

    private function cachedHash(string $file): string
    {
        $cache = $this->readCache();

        $this->assertArrayHasKey($file, $cache);
        assert(isset($cache[$file]));

        return $cache[$file]['sha256'];
    }

    /**
     * @return array<string, string>
     */
    private function cachedDependencies(string $file): array
    {
        $cache = $this->readCache();

        $this->assertArrayHasKey($file, $cache);
        assert(isset($cache[$file]));

        return $cache[$file]['dependencies'];
    }

    /**
     * @param non-empty-string $directory
     */
    private function createDirectory(string $directory): bool
    {
        return !(!is_dir($directory) && !@mkdir($directory, 0o777, true) && !is_dir($directory));
    }

    /**
     * @param non-empty-string $directory
     */
    private function deleteDirectory(string $directory): void
    {
        if (is_file($directory)) {
            @unlink($directory);

            return;
        }

        if (!is_dir($directory)) {
            return;
        }

        $paths = glob(rtrim($directory, '/') . '/*');

        assert($paths !== false);

        foreach ($paths as $path) {
            assert($path !== '');

            $this->deleteDirectory($path);
        }

        @rmdir($directory);
    }
}
