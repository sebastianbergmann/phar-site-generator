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
            $buffer .= sprintf(
                "rewrite ^/%s.phar$ /%s-%s.phar redirect;\n",
                $release->package(),
                $release->package(),
                $release->version(),
            );

            $buffer .= sprintf(
                "rewrite ^/%s.phar.asc$ /%s-%s.phar.asc redirect;\n",
                $release->package(),
                $release->package(),
                $release->version(),
            );
        }

        foreach ($releases->latestReleasesPerPackageAndMajorVersion() as $release) {
            $buffer .= sprintf(
                "rewrite ^/%s-%s.phar$ /%s-%s.phar redirect;\n",
                $release->package(),
                $release->majorVersion(),
                $release->package(),
                $release->version(),
            );

            $buffer .= sprintf(
                "rewrite ^/%s-%s.phar.asc$ /%s-%s.phar.asc redirect;\n",
                $release->package(),
                $release->majorVersion(),
                $release->package(),
                $release->version(),
            );
        }

        foreach ($releases->latestReleasesPerPackageAndMinorVersion() as $release) {
            $buffer .= sprintf(
                "rewrite ^/%s-%s.phar$ /%s-%s.phar redirect;\n",
                $release->package(),
                $release->minorVersion(),
                $release->package(),
                $release->version(),
            );

            $buffer .= sprintf(
                "rewrite ^/%s-%s.phar.asc$ /%s-%s.phar.asc redirect;\n",
                $release->package(),
                $release->minorVersion(),
                $release->package(),
                $release->version(),
            );
        }

        file_put_contents($target, $buffer);
    }
}
