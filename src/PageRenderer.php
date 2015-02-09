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

class PageRenderer extends AbstractRenderer
{
    /**
     * @param ReleaseCollection $releases
     */
    public function render(ReleaseCollection $releases)
    {
        $latestReleases = '';

        foreach ($releases->latestReleasesSortedByPackageName() as $release) {
            $latestReleases .= $this->renderRelease($release, true);
        }

        $allReleases = '';

        foreach ($releases->allReleases() as $release) {
            $allReleases .= $this->renderRelease($release);
        }

        $page = new \Text_Template(__DIR__ . '/templates/page.html');

        $page->setVar(
            [
                'domain'          => $this->domain(),
                'latest_releases' => $latestReleases,
                'all_releases'    => $allReleases
            ]
        );

        $page->renderTo($this->target());
    }

    /**
     * @param  Release $release
     * @param  bool    $latest
     * @return string
     */
    private function renderRelease(Release $release, $latest = false)
    {
        $item = new \Text_Template(__DIR__ . '/templates/item.html');

        $item->setVar(
            [
                'domain'      => $this->domain(),
                'package'     => $release->package(),
                'version'     => $release->version(),
                'date'        => $release->date(),
                'size'        => $release->size(),
                'sha256'      => $release->sha256(),
                'strongOpen'  => $latest ? '<strong>' : '',
                'strongClose' => $latest ? '</strong>' : '',
                'manifest'    => implode(
                    '',
                    array_map(
                        function ($item) {
                            return '<li>' . $item . '</li>';
                        },
                        $release->manifest()
                    )
                )
            ]
        );

        return $item->render();
    }
}
