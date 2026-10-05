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

use const JSON_PRETTY_PRINT;
use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_SLASHES;
use function assert;
use function basename;
use function error_clear_last;
use function error_get_last;
use function file_get_contents;
use function file_put_contents;
use function hash_file;
use function is_array;
use function is_file;
use function is_int;
use function is_string;
use function json_decode;
use function json_encode;
use function preg_match;
use function preg_replace;
use function rename;
use function sprintf;
use function unlink;

/**
 * Remembers the SHA-256 hashes of PHAR files between runs so that only
 * PHAR files that have not been seen before need to be read and hashed.
 *
 * A cached hash is only used when the size and modification time of the
 * PHAR file still match the values that were recorded together with it.
 */
final class HashCache
{
    /**
     * @var array<string, array{bytes: int, mtime: int, sha256: non-empty-string}>
     */
    private array $cached;

    /**
     * @var array<string, array{bytes: int, mtime: int, sha256: non-empty-string}>
     */
    private array $used = [];
    private int $hits   = 0;
    private int $misses = 0;

    /**
     * A cache file that does not exist, cannot be read, or is malformed
     * results in an empty cache: every PHAR file is hashed (again).
     */
    public static function fromFile(string $filename): self
    {
        if (!is_file($filename)) {
            return new self;
        }

        $buffer = file_get_contents($filename);

        if ($buffer === false) {
            return new self;
        }

        $data = json_decode($buffer, true);

        if (!is_array($data)) {
            return new self;
        }

        $cached = [];

        foreach ($data as $key => $entry) {
            if (!is_string($key) ||
                !is_array($entry) ||
                !isset($entry['bytes'], $entry['mtime'], $entry['sha256']) ||
                !is_int($entry['bytes']) ||
                !is_int($entry['mtime']) ||
                !is_string($entry['sha256']) ||
                preg_match('/^[0-9a-f]{64}$/', $entry['sha256']) !== 1) {
                continue;
            }

            $cached[$key] = [
                'bytes'  => $entry['bytes'],
                'mtime'  => $entry['mtime'],
                'sha256' => $entry['sha256'],
            ];
        }

        return new self($cached);
    }

    /**
     * @param array<string, array{bytes: int, mtime: int, sha256: non-empty-string}> $cached
     */
    public function __construct(array $cached = [])
    {
        $this->cached = $cached;
    }

    /**
     * @return non-empty-string
     */
    public function sha256(string $pathname, int $bytes, int $mtime): string
    {
        $key = basename($pathname);

        if (isset($this->cached[$key]) &&
            $this->cached[$key]['bytes'] === $bytes &&
            $this->cached[$key]['mtime'] === $mtime) {
            $sha256 = $this->cached[$key]['sha256'];

            $this->hits++;
        } else {
            $sha256 = hash_file('sha256', $pathname);

            assert($sha256 !== false);

            $this->misses++;
        }

        $this->used[$key] = [
            'bytes'  => $bytes,
            'mtime'  => $mtime,
            'sha256' => $sha256,
        ];

        return $sha256;
    }

    public function hits(): int
    {
        return $this->hits;
    }

    public function misses(): int
    {
        return $this->misses;
    }

    /**
     * Only the hashes of PHAR files that were seen in this run are written,
     * hashes of PHAR files that no longer exist are dropped from the cache.
     *
     * @throws RuntimeException
     */
    public function save(string $filename): void
    {
        $buffer        = json_encode($this->used, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . "\n";
        $temporaryFile = $filename . '.tmp';

        error_clear_last();

        // Write to a temporary file first so that an interrupted run cannot
        // leave a truncated cache file behind
        if (@file_put_contents($temporaryFile, $buffer) !== false && @rename($temporaryFile, $filename)) {
            return;
        }

        $error = error_get_last();

        @unlink($temporaryFile);

        throw new RuntimeException(
            sprintf(
                'Cache file "%s" could not be written: %s',
                $filename,
                $error !== null ? preg_replace('/^\w+\(.*?\): /', '', $error['message']) : 'unknown error',
            ),
        );
    }
}
