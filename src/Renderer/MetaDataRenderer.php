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

final class MetaDataRenderer extends AbstractRenderer
{
    public function render(ReleaseCollection $releases): void
    {
        foreach ($releases->latestReleases() as $release) {
            \file_put_contents(
                $this->target() . $release->package(),
                $release->version()
            );
        }

        foreach ($releases->latestReleasesPerPackageAndMajorVersion() as $release) {
            \file_put_contents(
                $this->target() . $release->package() . '-' . $release->majorVersion(),
                $release->version()
            );
        }

        foreach ($releases->latestReleasesPerPackageAndMinorVersion() as $release) {
            \file_put_contents(
                $this->target() . $release->package() . '-' . $release->minorVersion(),
                $release->version()
            );
        }
    }
}
