<?php
/*
 * This file is part of phar-site-generator.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SebastianBergmann\PharSiteGenerator;

class Collector
{
    /**
     * @param  string $directory
     * @return ReleaseCollection
     */
    public function collect($directory)
    {
        $releases = new ReleaseCollection;

        foreach (new \GlobIterator($directory . '/*.phar') as $file) {
            if (!$file->isLink() &&
                stripos($file->getBasename(), 'alpha') === false &&
                stripos($file->getBasename(), 'beta') === false) {
                $parts    = explode('-', $file->getBasename('.phar'));
                $version  = array_pop($parts);
                $name     = join('-', $parts);
                $manifest = array();

                if (strpos(file_get_contents($file->getPathname()), '--manifest')) {
                    @exec($file->getPathname() . ' --manifest 2> /dev/null', $manifest);
                }

                $releases->add(
                    new Release(
                        $name,
                        $version,
                        $manifest,
                        date(DATE_W3C, $file->getMTime()),
                        $this->humanFilesize($file->getSize()),
                        sha1_file($file->getPathname())
                    )
                );
            }
        }

        return $releases;
    }

    /**
     * @param  integer $bytes
     * @return string
     */
    private function humanFilesize($bytes)
    {
        $sz = 'BKMGTP';
        $factor = floor((strlen($bytes) - 1) / 3);
        return sprintf("%.2f", $bytes / pow(1024, $factor)) . @$sz[$factor];
    }
}
