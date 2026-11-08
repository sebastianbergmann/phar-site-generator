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
use function floor;
use function hash_file;
use function implode;
use function is_file;
use function sprintf;
use function strlen;
use GlobIterator;
use SplFileInfo;

final readonly class ReleaseCollector
{
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
                $sha256       = $this->cache->lookup($pathname, $bytes, $mtime);

                if ($sha256 === null) {
                    $sha256 = hash_file('sha256', $pathname);

                    assert($sha256 !== false);
                }

                $this->cache->store($pathname, $bytes, $mtime, $sha256);

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
                    ),
                );
            }
        }

        return $releases;
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
