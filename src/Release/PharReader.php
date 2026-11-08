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

use function bzdecompress;
use function crc32;
use function function_exists;
use function gzinflate;
use function in_array;
use function is_string;
use function ord;
use function strlen;
use function strpos;
use function substr;

/**
 * Reads a file from a PHAR without using the phar extension.
 *
 * The phar extension keeps every archive that it opened, for instance using
 * the phar:// stream wrapper, in memory until the process ends. Reading a file
 * from each of the hundreds of PHARs in a directory that way exhausts the
 * memory limit.
 *
 * Only PHARs in the phar file format are supported, which is the format that
 * is used by executable PHARs, but not PHARs in the tar or zip file format.
 *
 * @see https://www.php.net/manual/en/phar.fileformat.phar.php
 */
final readonly class PharReader
{
    private const string HALT_COMPILER  = '__HALT_COMPILER();';
    private const int COMPRESSION_MASK  = 0x0000F000;
    private const int COMPRESSION_NONE  = 0x00000000;
    private const int COMPRESSION_GZIP  = 0x00001000;
    private const int COMPRESSION_BZIP2 = 0x00002000;

    /**
     * Returns the contents of a file in a PHAR, or null when the PHAR does
     * not contain the file, or when the PHAR or the file cannot be read.
     *
     * @param string $phar Contents of the PHAR
     * @param string $file Path of the file relative to the root of the PHAR
     */
    public function read(string $phar, string $file): ?string
    {
        try {
            return $this->file($phar, $file);
        } catch (RuntimeException) {
            return null;
        }
    }

    /**
     * @throws RuntimeException
     */
    private function file(string $phar, string $file): ?string
    {
        $offset         = $this->manifestOffset($phar);
        $manifestLength = $this->uint32($phar, $offset);
        $numberOfFiles  = $this->uint32($phar, $offset + 4);

        // The contents of the files follow the manifest, in the order in
        // which the files are listed in the manifest
        $manifestEnd   = $offset + 4 + $manifestLength;
        $contentOffset = $manifestEnd;

        // Skip the length of the manifest, the number of files, the API
        // version (2 bytes), and the global flags
        $offset += 14;

        // Skip the alias and the metadata of the PHAR
        $offset += 4 + $this->uint32($phar, $offset);
        $offset += 4 + $this->uint32($phar, $offset);

        for ($i = 0; $i < $numberOfFiles; $i++) {
            $filenameLength = $this->uint32($phar, $offset);
            $filename       = substr($phar, $offset + 4, $filenameLength);

            $offset += 4 + $filenameLength;

            // The timestamp at $offset + 4 is not needed
            $size           = $this->uint32($phar, $offset);
            $compressedSize = $this->uint32($phar, $offset + 8);
            $crc32          = $this->uint32($phar, $offset + 12);
            $flags          = $this->uint32($phar, $offset + 16);

            $offset += 24 + $this->uint32($phar, $offset + 20);

            if ($offset > $manifestEnd) {
                throw new RuntimeException('Manifest of PHAR is malformed');
            }

            if ($filename === $file) {
                return $this->contents(
                    substr($phar, $contentOffset, $compressedSize),
                    $flags,
                    $size,
                    $crc32,
                );
            }

            $contentOffset += $compressedSize;
        }

        return null;
    }

    /**
     * Like the phar extension, the manifest is expected after the first
     * __HALT_COMPILER(); token and an optional " ?>" or "\n?>" that may
     * be followed by "\n" or "\r\n".
     *
     * @throws RuntimeException
     */
    private function manifestOffset(string $phar): int
    {
        $offset = strpos($phar, self::HALT_COMPILER);

        if ($offset === false) {
            throw new RuntimeException('File is not a PHAR');
        }

        $offset += strlen(self::HALT_COMPILER);

        if (in_array(substr($phar, $offset, 3), [' ?>', "\n?>"], true)) {
            $offset += 3;

            if (substr($phar, $offset, 2) === "\r\n") {
                $offset += 2;
            } elseif (substr($phar, $offset, 1) === "\n") {
                $offset++;
            }
        }

        return $offset;
    }

    /**
     * @throws RuntimeException
     */
    private function contents(string $data, int $flags, int $size, int $crc32): string
    {
        $contents = match ($flags & self::COMPRESSION_MASK) {
            self::COMPRESSION_NONE  => $data,
            self::COMPRESSION_GZIP  => function_exists('gzinflate') ? @gzinflate($data) : false,
            self::COMPRESSION_BZIP2 => function_exists('bzdecompress') ? @bzdecompress($data) : false,
            default                 => false,
        };

        if (!is_string($contents) || strlen($contents) !== $size || crc32($contents) !== $crc32) {
            throw new RuntimeException('File in PHAR cannot be read');
        }

        return $contents;
    }

    /**
     * Reads an unsigned 32-bit integer in little-endian byte order.
     *
     * @throws RuntimeException
     */
    private function uint32(string $buffer, int $offset): int
    {
        if ($offset < 0 || $offset + 4 > strlen($buffer)) {
            throw new RuntimeException('PHAR is truncated');
        }

        return ord($buffer[$offset]) |
               ord($buffer[$offset + 1]) << 8 |
               ord($buffer[$offset + 2]) << 16 |
               ord($buffer[$offset + 3]) << 24;
    }
}
