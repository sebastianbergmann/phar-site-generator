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

class NginxConfigRenderer
{
    /**
     * @param ReleaseCollection $releases
     */
    public function render(ReleaseCollection $releases, $target)
    {
        $buffer = '';

        foreach ($releases->latestReleases() as $release) {
            $buffer .= sprintf(
                "rewrite ^/%s.phar$ /%s-%s.phar redirect;\n",
                $release->package(),
                $release->package(),
                $release->version()
            );

            $buffer .= sprintf(
                "rewrite ^/%s.phar.asc$ /%s-%s.phar.asc redirect;\n",
                $release->package(),
                $release->package(),
                $release->version()
            );
        }

        file_put_contents($target, $buffer);
    }
}
