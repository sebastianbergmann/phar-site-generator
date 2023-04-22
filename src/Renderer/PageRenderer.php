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

use InvalidArgumentException;
use RuntimeException;
use SebastianBergmann\Template\Template;

final class PageRenderer extends AbstractRenderer
{
    /**
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    public function render(ReleaseCollection $releases): void
    {
        $latestReleases = '';

        foreach ($releases->latestReleasesSortedByPackageName() as $release) {
            $latestReleases .= $this->renderRelease($release);
        }

        $allReleases = '';

        foreach ($releases->allReleases() as $release) {
            $allReleases .= $this->renderRelease($release);
        }

        $page = new Template(__DIR__ . '/../templates/page.html');

        $page->setVar(
            [
                'domain'          => $this->domain(),
                'latest_releases' => $latestReleases,
                'all_releases'    => $allReleases,
            ]
        );

        $page->renderTo($this->target());
    }

    /**
     * @throws InvalidArgumentException
     */
    private function renderRelease(Release $release): string
    {
        $item = new Template(__DIR__ . '/../templates/item.html');

        $item->setVar(
            [
                'domain'  => $this->domain(),
                'package' => $release->package(),
                'version' => $release->version(),
                'date'    => $release->date(),
                'size'    => $release->size(),
                'sha256'  => $release->sha256(),
            ]
        );

        return $item->render();
    }
}
