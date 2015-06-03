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

class MetaDataRenderer extends AbstractRenderer
{
    /**
     * @param ReleaseCollection $releases
     */
    public function render(ReleaseCollection $releases)
    {
        foreach ($releases->latestReleases() as $release) {
            if (empty($release->package()))  {
                var_dump($release);
            }

            file_put_contents(
                $this->target() . $release->package(),
                $release->version()
            );
        }
    }
}
