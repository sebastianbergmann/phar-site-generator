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
use const JSON_THROW_ON_ERROR;
use function assert;
use function bin2hex;
use function file_put_contents;
use function glob;
use function is_dir;
use function json_encode;
use function mkdir;
use function random_bytes;
use function rmdir;
use function str_repeat;
use function sys_get_temp_dir;
use function unlink;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Medium;
use PHPUnit\Framework\TestCase;

#[Medium]
#[CoversClass(Cache::class)]
final class CacheTest extends TestCase
{
    private string $directory;

    /**
     * @return non-empty-array<non-empty-string, array{0: array<mixed>}>
     */
    public static function invalidEntries(): array
    {
        $valid = self::entry();

        return [
            'entry is not an array'                    => [['package-1.2.3.phar' => 'invalid']],
            'entry has no key'                         => [[$valid]],
            'bytes are missing'                        => [['package-1.2.3.phar' => ['mtime' => 1, 'sha256' => self::sha256(), 'dependencies' => []]]],
            'modification time is missing'             => [['package-1.2.3.phar' => ['bytes' => 1, 'sha256' => self::sha256(), 'dependencies' => []]]],
            'hash is missing'                          => [['package-1.2.3.phar' => ['bytes' => 1, 'mtime' => 1, 'dependencies' => []]]],
            'dependencies are missing'                 => [['package-1.2.3.phar' => ['bytes' => 1, 'mtime' => 1, 'sha256' => self::sha256()]]],
            'bytes are not an integer'                 => [['package-1.2.3.phar' => ['bytes' => '1'] + $valid]],
            'modification time is not an integer'      => [['package-1.2.3.phar' => ['mtime' => '1'] + $valid]],
            'hash is not a string'                     => [['package-1.2.3.phar' => ['sha256' => 1] + $valid]],
            'hash is not a SHA-256 hash'               => [['package-1.2.3.phar' => ['sha256' => str_repeat('A', 64)] + $valid]],
            'dependencies are not an array'            => [['package-1.2.3.phar' => ['dependencies' => 'invalid'] + $valid]],
            'dependency has no name'                   => [['package-1.2.3.phar' => ['dependencies' => ['1.0.0']] + $valid]],
            'dependency has empty name'                => [['package-1.2.3.phar' => ['dependencies' => ['' => '1.0.0']] + $valid]],
            'version of dependency is not a string'    => [['package-1.2.3.phar' => ['dependencies' => ['example/dependency' => 1]] + $valid]],
            'version of dependency is an empty string' => [['package-1.2.3.phar' => ['dependencies' => ['example/dependency' => '']] + $valid]],
        ];
    }

    public function testDoesNotHaveInformationAboutPharFileThatWasNotCached(): void
    {
        $cache = new Cache;

        $this->assertNull($cache->lookup('/path/to/package-1.2.3.phar', 3300, 1772323200));
        $this->assertSame(0, $cache->hits());
        $this->assertSame(1, $cache->misses());
    }

    public function testHasInformationAboutPharFileWhenItsSizeAndModificationTimeMatch(): void
    {
        $cache = new Cache(['package-1.2.3.phar' => self::entry()]);

        $this->assertSame(
            [
                'sha256'       => self::sha256(),
                'dependencies' => ['example/dependency' => '1.0.0'],
            ],
            $cache->lookup('/path/to/package-1.2.3.phar', 3300, 1772323200),
        );

        $this->assertSame(1, $cache->hits());
        $this->assertSame(0, $cache->misses());
    }

    public function testDoesNotHaveInformationAboutPharFileWhenItsSizeDiffers(): void
    {
        $cache = new Cache(['package-1.2.3.phar' => self::entry()]);

        $this->assertNull($cache->lookup('/path/to/package-1.2.3.phar', 3301, 1772323200));
        $this->assertSame(0, $cache->hits());
        $this->assertSame(1, $cache->misses());
    }

    public function testDoesNotHaveInformationAboutPharFileWhenItsModificationTimeDiffers(): void
    {
        $cache = new Cache(['package-1.2.3.phar' => self::entry()]);

        $this->assertNull($cache->lookup('/path/to/package-1.2.3.phar', 3300, 1772323201));
        $this->assertSame(0, $cache->hits());
        $this->assertSame(1, $cache->misses());
    }

    public function testCanBeLoadedFromFile(): void
    {
        $this->writeCacheFile(['package-1.2.3.phar' => self::entry()]);

        $cache = Cache::fromFile($this->cacheFile());

        $this->assertSame(
            [
                'sha256'       => self::sha256(),
                'dependencies' => ['example/dependency' => '1.0.0'],
            ],
            $cache->lookup('/path/to/package-1.2.3.phar', 3300, 1772323200),
        );
    }

    public function testIsEmptyWhenLoadedFromFileThatDoesNotExist(): void
    {
        $cache = Cache::fromFile($this->cacheFile());

        $this->assertNull($cache->lookup('/path/to/package-1.2.3.phar', 3300, 1772323200));
    }

    public function testIsEmptyWhenLoadedFromFileThatIsNotValidJson(): void
    {
        file_put_contents($this->cacheFile(), '{"package-1.2.3.phar": ');

        $cache = Cache::fromFile($this->cacheFile());

        $this->assertNull($cache->lookup('/path/to/package-1.2.3.phar', 3300, 1772323200));
    }

    public function testIsEmptyWhenLoadedFromFileThatDoesNotContainJsonObject(): void
    {
        file_put_contents($this->cacheFile(), '"package-1.2.3.phar"');

        $cache = Cache::fromFile($this->cacheFile());

        $this->assertNull($cache->lookup('/path/to/package-1.2.3.phar', 3300, 1772323200));
    }

    /**
     * @param array<mixed> $entries
     */
    #[DataProvider('invalidEntries')]
    public function testIgnoresInvalidEntriesWhenLoadedFromFile(array $entries): void
    {
        $this->writeCacheFile($entries + ['package-2.3.4.phar' => self::entry()]);

        $cache = Cache::fromFile($this->cacheFile());

        $this->assertNull($cache->lookup('/path/to/package-1.2.3.phar', 3300, 1772323200));
        $this->assertNotNull($cache->lookup('/path/to/package-2.3.4.phar', 3300, 1772323200));
    }

    public function testSavesOnlyInformationAboutPharFilesThatWereStored(): void
    {
        $cache = new Cache(['package-1.2.3.phar' => self::entry()]);

        $cache->store('/path/to/package-2.3.4.phar', 545, 1772323200, self::sha256(), ['example/dependency' => '2.0.0']);
        $cache->save($this->cacheFile());

        $this->assertStringEqualsFile(
            $this->cacheFile(),
            <<<'EOT'
{
    "package-2.3.4.phar": {
        "bytes": 545,
        "mtime": 1772323200,
        "sha256": "aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa",
        "dependencies": {
            "example/dependency": "2.0.0"
        }
    }
}

EOT,
        );

        $this->assertFileDoesNotExist($this->cacheFile() . '.tmp');
    }

    public function testSavedInformationCanBeLoaded(): void
    {
        $cache = new Cache;

        $cache->store('/path/to/package-1.2.3.phar', 3300, 1772323200, self::sha256(), ['example/dependency' => '1.0.0']);
        $cache->save($this->cacheFile());

        $cache = Cache::fromFile($this->cacheFile());

        $this->assertSame(
            [
                'sha256'       => self::sha256(),
                'dependencies' => ['example/dependency' => '1.0.0'],
            ],
            $cache->lookup('/path/to/package-1.2.3.phar', 3300, 1772323200),
        );
    }

    public function testCannotBeSavedToFileThatCannotBeWritten(): void
    {
        $cacheFile = $this->directory . '/does-not-exist/cache.json';

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageIs('Cache file "' . $cacheFile . '" could not be written: Failed to open stream: No such file or directory');

        (new Cache)->save($cacheFile);
    }

    public function testCannotBeSavedToFileThatCannotBeReplaced(): void
    {
        mkdir($this->cacheFile());

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageIs('Cache file "' . $this->cacheFile() . '" could not be written: Is a directory');

        (new Cache)->save($this->cacheFile());
    }

    public function testDoesNotLeaveTemporaryFileBehindWhenItCannotBeSaved(): void
    {
        mkdir($this->cacheFile());

        try {
            (new Cache)->save($this->cacheFile());
        } catch (RuntimeException) {
        }

        $this->assertFileDoesNotExist($this->cacheFile() . '.tmp');
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
            is_dir($file) ? rmdir($file) : unlink($file);
        }

        rmdir($this->directory);
    }

    private function cacheFile(): string
    {
        return $this->directory . '/cache.json';
    }

    /**
     * @param array<mixed> $entries
     */
    private function writeCacheFile(array $entries): void
    {
        file_put_contents($this->cacheFile(), json_encode($entries, JSON_THROW_ON_ERROR));
    }

    /**
     * @return array{bytes: int, mtime: int, sha256: non-empty-string, dependencies: array<non-empty-string, non-empty-string>}
     */
    private static function entry(): array
    {
        return [
            'bytes'        => 3300,
            'mtime'        => 1772323200,
            'sha256'       => self::sha256(),
            'dependencies' => ['example/dependency' => '1.0.0'],
        ];
    }

    /**
     * @return non-empty-string
     */
    private static function sha256(): string
    {
        return str_repeat('a', 64);
    }
}
