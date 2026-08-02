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

use const JSON_HEX_TAG;
use const JSON_PRETTY_PRINT;
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;
use function assert;
use function json_encode;
use function sprintf;
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
                'description'     => $this->description(),
                'structured_data' => $this->renderStructuredData($releases),
                'latest_releases' => $latestReleases,
                'all_releases'    => $allReleases,
            ],
        );

        $page->renderTo($this->target());
    }

    private function description(): string
    {
        return sprintf(
            'Download PHP Archives (PHAR) from %s. Every release is listed with its file size, ' .
            'last modification date, SHA-256 checksum, and detached OpenPGP signature.',
            $this->domain(),
        );
    }

    /**
     * Describes the latest release of each PHAR using schema.org vocabulary so that
     * search engines and other consumers do not have to parse the HTML table.
     */
    private function renderStructuredData(ReleaseCollection $releases): string
    {
        $items    = [];
        $position = 0;

        foreach ($releases->latestReleasesSortedByPackageName() as $release) {
            $url = sprintf(
                'https://%s/%s-%s.phar',
                $this->domain(),
                $release->package(),
                $release->version(),
            );

            $items[] = [
                '@type'    => 'ListItem',
                'position' => ++$position,
                'item'     => [
                    '@type'               => 'SoftwareApplication',
                    'name'                => $release->package(),
                    'softwareVersion'     => $release->version(),
                    'applicationCategory' => 'DeveloperApplication',
                    'operatingSystem'     => 'Any',
                    'dateModified'        => $release->date(),
                    'downloadUrl'         => $url,
                    'associatedMedia'     => [
                        '@type'       => 'DataDownload',
                        'contentUrl'  => $url,
                        'contentSize' => (string) $release->bytes(),
                        'sha256'      => $release->sha256(),
                    ],
                ],
            ];
        }

        $structuredData = [
            '@context' => 'https://schema.org',
            '@graph'   => [
                [
                    '@type'       => 'WebSite',
                    '@id'         => sprintf('https://%s/#website', $this->domain()),
                    'url'         => sprintf('https://%s/', $this->domain()),
                    'name'        => $this->domain(),
                    'description' => $this->description(),
                ],
                [
                    '@type'           => 'ItemList',
                    'name'            => sprintf('Latest releases on %s', $this->domain()),
                    'numberOfItems'   => $position,
                    'itemListElement' => $items,
                ],
            ],
        ];

        $json = json_encode(
            $structuredData,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG,
        );

        assert($json !== false);

        return $json;
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
            ],
        );

        return $item->render();
    }
}
