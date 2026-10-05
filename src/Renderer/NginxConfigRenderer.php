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

use function file_put_contents;
use function sprintf;

final class NginxConfigRenderer
{
    public function render(ReleaseCollection $releases, string $target): void
    {
        $buffer = '';

        foreach ($releases->latestReleases() as $release) {
            $buffer .= $this->redirects($release->package(), $release);
        }

        foreach ($releases->latestReleasesPerPackageAndMajorVersion() as $release) {
            $buffer .= $this->redirects($release->package() . '-' . $release->majorVersion(), $release);
        }

        foreach ($releases->latestReleasesPerPackageAndMinorVersion() as $release) {
            $buffer .= $this->redirects($release->package() . '-' . $release->minorVersion(), $release);
        }

        file_put_contents($target, $buffer);
    }

    private function redirects(string $alias, Release $release): string
    {
        $buffer = '';

        foreach ($this->suffixes($release) as $suffix) {
            $buffer .= sprintf(
                "rewrite ^/%s%s$ /%s%s redirect;\n",
                $alias,
                $suffix,
                $release->asString(),
                $suffix,
            );
        }

        return $buffer;
    }

    /**
     * @return non-empty-list<non-empty-string>
     */
    private function suffixes(Release $release): array
    {
        if ($release->hasSbom()) {
            return ['.phar', '.phar.asc', '.phar.cdx.xml', '.phar.cdx.xml.asc'];
        }

        return ['.phar', '.phar.asc'];
    }
}
