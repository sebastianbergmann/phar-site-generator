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

use const DATE_W3C;
use function array_pop;
use function array_slice;
use function assert;
use function date;
use function explode;
use function file_get_contents;
use function floor;
use function hash_file;
use function implode;
use function is_file;
use function preg_match;
use function sprintf;
use function strlen;
use function trim;
use GlobIterator;
use SplFileInfo;

final readonly class ReleaseCollector
{
    /**
     * Locations, relative to the root of the PHAR, of the file that is printed
     * by the --manifest option of PHARs that were built for this site.
     *
     * @var list<non-empty-string>
     */
    private const array MANIFEST_FILES = [
        'manifest.txt',
        'phar/manifest.txt',
    ];
    private Cache $cache;

    public function __construct(Cache $cache)
    {
        $this->cache = $cache;
    }

    public function collect(string $directory): ReleaseCollection
    {
        $releases = new ReleaseCollection;

        foreach (new GlobIterator($directory . '/*.phar') as $file) {
            assert($file instanceof SplFileInfo);

            if (!$file->isLink()) {
                $parts        = explode('-', $file->getBasename('.phar'));
                $version      = array_pop($parts);
                $majorVersion = explode('.', $version)[0];
                $minorVersion = implode('.', array_slice(explode('.', $version), 0, 2));
                $name         = implode('-', $parts);
                $pathname     = $file->getPathname();
                $bytes        = $file->getSize();
                $mtime        = $file->getMTime();
                $cached       = $this->cache->lookup($pathname, $bytes, $mtime);

                if ($cached !== null) {
                    $sha256       = $cached['sha256'];
                    $dependencies = $cached['dependencies'];
                } else {
                    $sha256       = hash_file('sha256', $pathname);
                    $dependencies = $this->bundledDependencies($pathname);

                    assert($sha256 !== false);
                }

                $this->cache->store($pathname, $bytes, $mtime, $sha256, $dependencies);

                assert($name !== '');
                assert($version !== '');
                assert($majorVersion !== '');
                assert($minorVersion !== '');
                assert($bytes > 0);

                $releases->add(
                    new Release(
                        $name,
                        $version,
                        $majorVersion,
                        $minorVersion,
                        date(DATE_W3C, $mtime),
                        $this->humanReadableSize($bytes),
                        $bytes,
                        $sha256,
                        is_file($pathname . '.cdx.xml'),
                        $dependencies,
                    ),
                );
            }
        }

        return $releases;
    }

    /**
     * Reads the dependencies that are bundled in a PHAR from the manifest that
     * is printed by its --manifest option. The manifest file is read from the
     * PHAR using the phar:// stream wrapper, the PHAR is not executed.
     *
     * The first line of the manifest names the package of the PHAR itself,
     * each following line names a bundled dependency: "vendor/package: version".
     *
     * An empty array is returned for files that are not PHARs, cannot be
     * read, or do not contain a manifest.
     *
     * @return array<non-empty-string, non-empty-string>
     */
    private function bundledDependencies(string $pathname): array
    {
        $manifest = $this->manifest($pathname);

        if ($manifest === null) {
            return [];
        }

        $dependencies = [];

        foreach (array_slice(explode("\n", $manifest), 1) as $line) {
            if (preg_match('#^(?<name>[^\s:]+/[^\s:]+): (?<version>\S+)$#', trim($line), $matches) === 1) {
                $dependencies[$matches['name']] = $matches['version'];
            }
        }

        return $dependencies;
    }

    private function manifest(string $pathname): ?string
    {
        foreach (self::MANIFEST_FILES as $manifestFile) {
            $manifest = @file_get_contents('phar://' . $pathname . '/' . $manifestFile);

            if ($manifest !== false) {
                return $manifest;
            }
        }

        return null;
    }

    /**
     * @return non-empty-string
     */
    private function humanReadableSize(int $bytes): string
    {
        $sz     = 'BKMGTP';
        $factor = (int) floor((strlen((string) $bytes) - 1) / 3);

        return sprintf('%.2f', $bytes / 1024 ** $factor) . @$sz[$factor];
    }
}
