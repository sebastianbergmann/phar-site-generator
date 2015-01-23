<?php
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
     * @param Release $release
     * @param boolean $latest
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
                'sha1'        => $release->sha1(),
                'strongOpen'  => $latest ? '<strong>' : '',
                'strongClose' => $latest ? '</strong>' : '',
                'manifest'    => join(
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
