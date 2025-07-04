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

use const PHP_EOL;
use function file_put_contents;
use function sprintf;

final class ApacheConfigRenderer
{
    public function render(ReleaseCollection $releases, string $target): void
    {
        $buffer = <<<'EOT'
AddType application/octet-stream .phar
AddType application/pgp-signature .phar.asc


EOT;

        foreach ($releases->latestReleases() as $release) {
            $buffer .= sprintf(
                'Redirect "/%s.phar" "/%s-%s.phar"' . PHP_EOL,
                $release->package(),
                $release->package(),
                $release->version(),
            );

            $buffer .= sprintf(
                'Redirect "/%s.phar.asc" "/%s-%s.phar.asc"' . PHP_EOL,
                $release->package(),
                $release->package(),
                $release->version(),
            );
        }

        foreach ($releases->latestReleasesPerPackageAndMajorVersion() as $release) {
            $buffer .= sprintf(
                'Redirect "/%s-%s.phar" "/%s-%s.phar"' . PHP_EOL,
                $release->package(),
                $release->majorVersion(),
                $release->package(),
                $release->version(),
            );

            $buffer .= sprintf(
                'Redirect "/%s-%s.phar.asc" "/%s-%s.phar.asc"' . PHP_EOL,
                $release->package(),
                $release->majorVersion(),
                $release->package(),
                $release->version(),
            );
        }

        foreach ($releases->latestReleasesPerPackageAndMinorVersion() as $release) {
            $buffer .= sprintf(
                'Redirect "/%s-%s.phar" "/%s-%s.phar"' . PHP_EOL,
                $release->package(),
                $release->minorVersion(),
                $release->package(),
                $release->version(),
            );

            $buffer .= sprintf(
                'Redirect "/%s-%s.phar.asc" "/%s-%s.phar.asc"' . PHP_EOL,
                $release->package(),
                $release->minorVersion(),
                $release->package(),
                $release->version(),
            );
        }

        file_put_contents($target, $buffer);
    }
}
