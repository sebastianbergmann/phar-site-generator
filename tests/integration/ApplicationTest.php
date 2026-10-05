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
    private const string SHA256 = 'a72427688cadc3d3a299cc6658839011bdcbb7337493cf903d22c14864791ab1';

    public function testGeneratesSite(): void
    {
        $this->generate();

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

    public function testWritesHashCache(): void
    {
        $this->generate();

        $this->assertSame(
            [
                'package-1.2.3.phar' => [
                    'bytes'  => 3300,
                    'mtime'  => $this->mtime('package-1.2.3.phar'),
                    'sha256' => self::SHA256,
                ],
                'package-2.3.4.phar' => [
                    'bytes'  => 3300,
                    'mtime'  => $this->mtime('package-2.3.4.phar'),
                    'sha256' => self::SHA256,
                ],
            ],
            $this->readHashCache(),
        );
    }

    public function testUsesCachedHash(): void
    {
        $cached = str_repeat('0', 64);

        $this->writeHashCache(
            [
                'package-2.3.4.phar' => [
                    'bytes'  => 3300,
                    'mtime'  => $this->mtime('package-2.3.4.phar'),
                    'sha256' => $cached,
                ],
            ],
        );

        $this->generate();

        $phive = file_get_contents('/tmp/phar.example.org/public/phive.xml');

        $this->assertIsString($phive);
        $this->assertStringContainsString('value="' . $cached . '"', $phive);
        $this->assertStringContainsString('value="' . self::SHA256 . '"', $phive);
        $this->assertSame($cached, $this->cachedHash('package-2.3.4.phar'));
    }

    public function testDoesNotUseCachedHashWhenModificationTimeDiffers(): void
    {
        $this->writeHashCache(
            [
                'package-2.3.4.phar' => [
                    'bytes'  => 3300,
                    'mtime'  => $this->mtime('package-2.3.4.phar') - 1,
                    'sha256' => str_repeat('0', 64),
                ],
            ],
        );

        $this->generate();

        $this->assertFileEquals(__DIR__ . '/../expectations/public/phive.xml', '/tmp/phar.example.org/public/phive.xml');
        $this->assertSame(self::SHA256, $this->cachedHash('package-2.3.4.phar'));
    }

    public function testDoesNotUseCachedHashWhenSizeDiffers(): void
    {
        $this->writeHashCache(
            [
                'package-2.3.4.phar' => [
                    'bytes'  => 3301,
                    'mtime'  => $this->mtime('package-2.3.4.phar'),
                    'sha256' => str_repeat('0', 64),
                ],
            ],
        );

        $this->generate();

        $this->assertFileEquals(__DIR__ . '/../expectations/public/phive.xml', '/tmp/phar.example.org/public/phive.xml');
        $this->assertSame(self::SHA256, $this->cachedHash('package-2.3.4.phar'));
    }

    public function testRemovesHashesOfPharFilesThatNoLongerExistFromCache(): void
    {
        $this->writeHashCache(
            [
                'package-0.1.0.phar' => [
                    'bytes'  => 3300,
                    'mtime'  => 0,
                    'sha256' => str_repeat('0', 64),
                ],
            ],
        );

        $this->generate();

        $this->assertSame(['package-1.2.3.phar', 'package-2.3.4.phar'], array_keys($this->readHashCache()));
    }

    public function testIgnoresMalformedHashCache(): void
    {
        file_put_contents('/tmp/phar.example.org/cache.json', '{"package-2.3.4.phar": ');

        $this->generate();

        $this->assertFileEquals(__DIR__ . '/../expectations/public/phive.xml', '/tmp/phar.example.org/public/phive.xml');
        $this->assertSame(self::SHA256, $this->cachedHash('package-2.3.4.phar'));
    }

    public function testFailsWhenHashCacheCannotBeWritten(): void
    {
        $this->createDirectory('/tmp/phar.example.org/cache.json.tmp');

        $this->expectOutputRegex('#Cache file "/tmp/phar\.example\.org/cache\.json" could not be written: Failed to open stream: Is a directory#');

        $this->assertSame(1, (new Application)->run(['phar-site-generator', __DIR__ . '/../fixture/configuration.xml']));
        $this->assertFileDoesNotExist('/tmp/phar.example.org/public/index.html');
    }

    public function testFailsWhenHashCacheCannotBeReplaced(): void
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

    private function generate(): void
    {
        (new Application)->generate(
            new Arguments(
                __DIR__ . '/../fixture/configuration.xml',
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
     * @param array<string, array{bytes: int, mtime: int, sha256: string}> $entries
     */
    private function writeHashCache(array $entries): void
    {
        file_put_contents('/tmp/phar.example.org/cache.json', json_encode($entries, JSON_THROW_ON_ERROR));
    }

    /**
     * @return array<string, array{bytes: int, mtime: int, sha256: string}>
     */
    private function readHashCache(): array
    {
        $buffer = file_get_contents('/tmp/phar.example.org/cache.json');

        assert($buffer !== false);

        /** @var array<string, array{bytes: int, mtime: int, sha256: string}> */
        return json_decode($buffer, true, flags: JSON_THROW_ON_ERROR);
    }

    private function cachedHash(string $file): string
    {
        $cache = $this->readHashCache();

        $this->assertArrayHasKey($file, $cache);
        assert(isset($cache[$file]));

        return $cache[$file]['sha256'];
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
