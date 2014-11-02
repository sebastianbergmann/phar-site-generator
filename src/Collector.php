<?php
namespace SebastianBergmann\PharSite;

class Collector
{
    /**
     * @param  string $directory
     * @return Release[]
     */
    public function collect($directory)
    {
        $releases = array();

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

                if (!isset($releases[$name])) {
                    $releases[$name] = array();
                }

                $releases[$name][$version] = new Release(
                    $name,
                    $version,
                    $manifest,
                    date(DATE_W3C, $file->getMTime()),
                    $this->humanFilesize($file->getSize()),
                    sha1_file($file->getPathname())
                );
            }
        }

        ksort($releases);

        foreach ($releases as $package => $versions) {
            uksort($versions, 'strnatcmp');

            $versions = array_reverse($versions, true);

            foreach ($versions as $release) {
                /** @var Release $release */
                $release->latest();
                break;
            }
        }

        return $releases;
    }

    /**
     * @param  integer $bytes
     * @return string
     */
    private function humanFilesize($bytes) {
        $sz = 'BKMGTP';
        $factor = floor((strlen($bytes) - 1) / 3);
        return sprintf("%.2f", $bytes / pow(1024, $factor)) . @$sz[$factor];
    }
}
