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

use function bzcompress;
use function count;
use function crc32;
use function file_get_contents;
use function gzdeflate;
use function pack;
use function strlen;
use function substr;
use function substr_replace;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;

#[Small]
#[CoversClass(PharReader::class)]
final class PharReaderTest extends TestCase
{
    private const int COMPRESSION_GZIP  = 0x00001000;
    private const int COMPRESSION_BZIP2 = 0x00002000;

    /**
     * @return non-empty-array<non-empty-string, array{0: non-empty-string}>
     */
    public static function stubs(): array
    {
        return [
            '__HALT_COMPILER(); followed by manifest'         => ['<?php __HALT_COMPILER();'],
            '__HALT_COMPILER(); followed by " ?>"'            => ['<?php __HALT_COMPILER(); ?>'],
            '__HALT_COMPILER(); followed by " ?>" and "\n"'   => ["<?php __HALT_COMPILER(); ?>\n"],
            '__HALT_COMPILER(); followed by " ?>" and "\r\n"' => ["<?php __HALT_COMPILER(); ?>\r\n"],
            '__HALT_COMPILER(); followed by "\n?>" and "\n"'  => ["<?php __HALT_COMPILER();\n?>\n"],
        ];
    }

    #[DataProvider('stubs')]
    public function testReadsFileFromPhar(string $stub): void
    {
        $phar = $this->phar(['manifest.txt' => $this->file('contents')], $stub);

        $this->assertSame('contents', (new PharReader)->read($phar, 'manifest.txt'));
    }

    public function testReadsFileThatFollowsOtherFilesInPhar(): void
    {
        $phar = $this->phar(
            [
                'first.txt'    => $this->file('first'),
                'second.txt'   => $this->file('second'),
                'manifest.txt' => $this->file('contents'),
            ],
        );

        $this->assertSame('contents', (new PharReader)->read($phar, 'manifest.txt'));
    }

    #[RequiresPhpExtension('zlib')]
    public function testReadsGzipCompressedFileFromPhar(): void
    {
        $compressed = gzdeflate('contents');

        $this->assertIsString($compressed);

        $phar = $this->phar(['manifest.txt' => $this->file('contents', $compressed, self::COMPRESSION_GZIP)]);

        $this->assertSame('contents', (new PharReader)->read($phar, 'manifest.txt'));
    }

    #[RequiresPhpExtension('bz2')]
    public function testReadsBzip2CompressedFileFromPhar(): void
    {
        $compressed = bzcompress('contents');

        $this->assertIsString($compressed);

        $phar = $this->phar(['manifest.txt' => $this->file('contents', $compressed, self::COMPRESSION_BZIP2)]);

        $this->assertSame('contents', (new PharReader)->read($phar, 'manifest.txt'));
    }

    public function testReadsFileFromPharThatWasCreatedUsingPharExtension(): void
    {
        $phar = file_get_contents(__DIR__ . '/../fixture/package-2.3.4.phar');

        $this->assertIsString($phar);
        $this->assertSame(
            "example/package: 2.3.4\nexample/dependency: 1.0.0\nexample/another-dependency: 2.0.0@3d8e0fbe5b2fb2e4d36c4d5be6e7ad0b6c3fd2d1\n",
            (new PharReader)->read($phar, 'manifest.txt'),
        );
    }

    public function testDoesNotReadFileThatIsNotInPhar(): void
    {
        $phar = $this->phar(['manifest.txt' => $this->file('contents')]);

        $this->assertNull((new PharReader)->read($phar, 'phar/manifest.txt'));
    }

    public function testDoesNotReadFileFromFileThatIsNotPhar(): void
    {
        $this->assertNull((new PharReader)->read('<?php print "Hello world!";', 'manifest.txt'));
    }

    public function testDoesNotReadFileFromTruncatedPhar(): void
    {
        $stub = '<?php __HALT_COMPILER();';
        $phar = $this->phar(['manifest.txt' => $this->file('contents')], $stub);

        $this->assertNull((new PharReader)->read(substr($phar, 0, strlen($stub) + 6), 'manifest.txt'));
    }

    public function testDoesNotReadFileFromPharWithMalformedManifest(): void
    {
        $stub = '<?php __HALT_COMPILER();';
        $phar = $this->phar(['manifest.txt' => $this->file('contents')], $stub);

        // Manifest length that ends the manifest before the end of the entry for manifest.txt
        $phar = substr_replace($phar, pack('V', 20), strlen($stub), 4);

        $this->assertNull((new PharReader)->read($phar, 'manifest.txt'));
    }

    public function testDoesNotReadFileWhoseSizeDoesNotMatch(): void
    {
        $phar = $this->phar(['manifest.txt' => $this->file('contents', size: 9)]);

        $this->assertNull((new PharReader)->read($phar, 'manifest.txt'));
    }

    public function testDoesNotReadFileWhoseChecksumDoesNotMatch(): void
    {
        $phar = $this->phar(['manifest.txt' => $this->file('contents', crc32: crc32('other contents'))]);

        $this->assertNull((new PharReader)->read($phar, 'manifest.txt'));
    }

    public function testDoesNotReadFileThatCannotBeDecompressed(): void
    {
        $phar = $this->phar(['manifest.txt' => $this->file('contents', 'not compressed', self::COMPRESSION_GZIP)]);

        $this->assertNull((new PharReader)->read($phar, 'manifest.txt'));
    }

    public function testDoesNotReadFileThatIsCompressedUsingUnknownCompression(): void
    {
        $phar = $this->phar(['manifest.txt' => $this->file('contents', flags: 0x00003000)]);

        $this->assertNull((new PharReader)->read($phar, 'manifest.txt'));
    }

    /**
     * @return array{data: string, flags: int, size: int, crc32: int}
     */
    private function file(string $contents, ?string $data = null, int $flags = 0, ?int $size = null, ?int $crc32 = null): array
    {
        return [
            'data'  => $data ?? $contents,
            'flags' => $flags,
            'size'  => $size ?? strlen($contents),
            'crc32' => $crc32 ?? crc32($contents),
        ];
    }

    /**
     * Creates a PHAR in the phar file format, without signature.
     *
     * @see https://www.php.net/manual/en/phar.fileformat.phar.php
     *
     * @param array<string, array{data: string, flags: int, size: int, crc32: int}> $files
     */
    private function phar(array $files, string $stub = "<?php __HALT_COMPILER(); ?>\r\n"): string
    {
        $alias    = 'example.phar';
        $metadata = '';
        $entries  = '';
        $data     = '';

        foreach ($files as $name => $file) {
            $entries .= pack('V', strlen($name)) . $name;
            $entries .= pack('VVVVVV', $file['size'], 0, strlen($file['data']), $file['crc32'], $file['flags'], 0);
            $data .= $file['data'];
        }

        $manifest = pack('V', count($files)) .
                    pack('v', 0x1110) .
                    pack('V', 0) .
                    pack('V', strlen($alias)) . $alias .
                    pack('V', strlen($metadata)) . $metadata .
                    $entries;

        return $stub . pack('V', strlen($manifest)) . $manifest . $data;
    }
}
