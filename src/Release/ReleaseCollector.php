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
use function date;
use function exec;
use function explode;
use function file;
use function file_exists;
use function file_get_contents;
use function floor;
use function hash_file;
use function implode;
use function is_executable;
use function sprintf;
use function stripos;
use function strlen;
use function strpos;
use GlobIterator;

class ReleaseCollector
{
    public function collect(string $directory): ReleaseCollection
    {
        $releases = new ReleaseCollection;

        foreach (new GlobIterator($directory . '/*.phar') as $file) {
            if (!$file->isLink() &&
                stripos($file->getBasename(), 'nightly') === false &&
                stripos($file->getBasename(), 'alpha') === false &&
                stripos($file->getBasename(), 'beta') === false) {
                $parts        = explode('-', $file->getBasename('.phar'));
                $version      = array_pop($parts);
                $majorVersion = explode('.', $version)[0];
                $minorVersion = implode('.', array_slice(explode('.', $version), 0, 2));
                $name         = implode('-', $parts);
                $manifest     = [];

                if (file_exists('phar://' . $file->getPathname() . '/manifest.txt')) {
                    $manifest = file('phar://' . $file->getPathname() . '/manifest.txt');
                } elseif (file_exists('phar://' . $file->getPathname() . '/phar/manifest.txt')) {
                    $manifest = file('phar://' . $file->getPathname() . '/phar/manifest.txt');
                } elseif (is_executable($file->getPathname()) &&
                          strpos(file_get_contents($file->getPathname()), '--manifest')) {
                    @exec($file->getPathname() . ' --manifest 2> /dev/null', $manifest);
                }

                $releases->add(
                    new Release(
                        $name,
                        $version,
                        $majorVersion,
                        $minorVersion,
                        $manifest,
                        date(DATE_W3C, $file->getMTime()),
                        $this->humanFilesize($file->getSize()),
                        hash_file('sha256', $file->getPathname())
                    )
                );
            }
        }

        return $releases;
    }

    private function humanFilesize(int $bytes): string
    {
        $sz     = 'BKMGTP';
        $factor = floor((strlen((string) $bytes) - 1) / 3);

        return sprintf('%.2f', $bytes / 1024 ** $factor) . @$sz[$factor];
    }
}
