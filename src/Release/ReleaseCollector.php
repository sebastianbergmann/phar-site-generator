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
use function sprintf;
use function strlen;
use GlobIterator;
use SplFileInfo;

final readonly class ReleaseCollector
{
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
                $hash         = hash_file('sha256', $file->getPathname());

                assert($name !== '');
                assert($version !== '');
                assert($majorVersion !== '');
                assert($minorVersion !== '');
                assert($hash !== false);

                $releases->add(
                    new Release(
                        $name,
                        $version,
                        $majorVersion,
                        $minorVersion,
                        date(DATE_W3C, $file->getMTime()),
                        $this->humanReadableSize($file->getSize()),
                        $hash,
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
