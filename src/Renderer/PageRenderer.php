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

use const ENT_HTML5;
use const ENT_QUOTES;
use const JSON_HEX_TAG;
use const JSON_PRETTY_PRINT;
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;
use function array_filter;
use function array_pop;
use function array_values;
use function assert;
use function count;
use function htmlspecialchars;
use function implode;
use function in_array;
use function json_encode;
use function rtrim;
use function sprintf;
use function substr;
use InvalidArgumentException;
use RuntimeException;
use SebastianBergmann\Template\Template;

final class PageRenderer extends AbstractRenderer
{
    /**
     * @var list<non-empty-string>
     */
    private readonly array $packagesIgnoredInLatestReleases;

    /**
     * @param non-empty-string       $target                          Directory, with a trailing directory separator, that index.html and all-releases.html are written to
     * @param list<non-empty-string> $packagesIgnoredInLatestReleases
     */
    public function __construct(string $target, string $domain, string $email, array $packagesIgnoredInLatestReleases)
    {
        parent::__construct($target, $domain, $email);

        $this->packagesIgnoredInLatestReleases = $packagesIgnoredInLatestReleases;
    }

    /**
     * Renders index.html, which lists the latest releases and links to
     * all-releases.html, which lists all releases.
     *
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    public function render(ReleaseCollection $releases): void
    {
        $structuredData = $this->renderStructuredData($releases);

        $this->renderPage(
            'index.html',
            sprintf('https://%s/', $this->domain()),
            $structuredData,
            $this->renderLatestReleases($releases),
        );

        $this->renderPage(
            'all-releases.html',
            sprintf('https://%s/all-releases.html', $this->domain()),
            $structuredData,
            $this->renderAllReleases($releases),
        );
    }

    /**
     * @param non-empty-string $file
     *
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    private function renderPage(string $file, string $url, string $structuredData, string $releases): void
    {
        $page = new Template(__DIR__ . '/../templates/page.html');

        $page->setVar(
            [
                'domain'          => $this->domain(),
                'url'             => $url,
                'description'     => $this->description(),
                'structured_data' => $structuredData,
                'releases'        => $releases,
            ],
        );

        $page->renderTo($this->target() . $file);
    }

    /**
     * @throws InvalidArgumentException
     */
    private function renderLatestReleases(ReleaseCollection $releases): string
    {
        $rows = '';

        foreach ($this->latestReleases($releases) as $release) {
            $rows .= $this->renderRelease($release);
        }

        $section = new Template(__DIR__ . '/../templates/latest-releases.html');

        $section->setVar(
            [
                'domain'   => $this->domain(),
                'caption'  => $this->latestReleasesCaption($releases),
                'releases' => $rows,
            ],
        );

        return rtrim($section->render());
    }

    /**
     * @throws InvalidArgumentException
     */
    private function renderAllReleases(ReleaseCollection $releases): string
    {
        $rows = '';

        foreach ($releases->allReleases() as $release) {
            $rows .= $this->renderRelease($release);
        }

        $section = new Template(__DIR__ . '/../templates/all-releases.html');

        $section->setVar(
            [
                'domain'   => $this->domain(),
                'releases' => $rows,
            ],
        );

        return rtrim($section->render());
    }

    /**
     * @return list<Release>
     */
    private function latestReleases(ReleaseCollection $releases): array
    {
        return array_values(
            array_filter(
                $releases->latestReleasesSortedByDateDescending(),
                fn (Release $release): bool => !in_array($release->package(), $this->packagesIgnoredInLatestReleases, true),
            ),
        );
    }

    /**
     * Names the packages that have releases but are not listed in the
     * "Latest Releases" section because they are ignored.
     */
    private function latestReleasesCaption(ReleaseCollection $releases): string
    {
        $caption = sprintf(
            'The most recent release of each PHAR distributed on %s',
            $this->domain(),
        );

        $ignoredPackages = [];

        foreach ($releases->latestReleasesSortedByPackageName() as $release) {
            if (in_array($release->package(), $this->packagesIgnoredInLatestReleases, true)) {
                $ignoredPackages[] = htmlspecialchars($release->package(), ENT_QUOTES | ENT_HTML5);
            }
        }

        if ($ignoredPackages === []) {
            return $caption;
        }

        return $caption . ', except for ' . $this->enumeration($ignoredPackages);
    }

    /**
     * @param non-empty-list<string> $items
     */
    private function enumeration(array $items): string
    {
        $last = array_pop($items);

        if ($items === []) {
            return $last;
        }

        if (count($items) === 1) {
            return $items[0] . ' and ' . $last;
        }

        return implode(', ', $items) . ', and ' . $last;
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

        foreach ($this->latestReleases($releases) as $release) {
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
                'domain'               => $this->domain(),
                'package'              => $release->package(),
                'version'              => $release->version(),
                'date'                 => $release->date(),
                'day'                  => substr($release->date(), 0, 10),
                'size'                 => $release->size(),
                'sha256'               => $release->sha256(),
                'sbom'                 => $this->renderSbom($release),
                'bundled_dependencies' => $this->renderBundledDependencies($release),
            ],
        );

        return $item->render();
    }

    /**
     * @throws InvalidArgumentException
     */
    private function renderSbom(Release $release): string
    {
        if (!$release->hasSbom()) {
            return '';
        }

        $sbom = new Template(__DIR__ . '/../templates/sbom.html');

        $sbom->setVar(
            [
                'domain'  => $this->domain(),
                'package' => $release->package(),
                'version' => $release->version(),
            ],
        );

        return rtrim($sbom->render());
    }

    /**
     * The dependencies bundled in a PHAR are read from the PHAR file, they
     * are escaped because they are not under the control of this tool.
     *
     * @throws InvalidArgumentException
     */
    private function renderBundledDependencies(Release $release): string
    {
        $dependencies = $release->bundledDependencies();

        if ($dependencies === []) {
            return '';
        }

        $items = '';

        foreach ($dependencies as $name => $version) {
            $items .= sprintf(
                '         <li>%s: %s</li>' . "\n",
                htmlspecialchars($name, ENT_QUOTES | ENT_HTML5),
                htmlspecialchars($version, ENT_QUOTES | ENT_HTML5),
            );
        }

        $template = new Template(__DIR__ . '/../templates/bundled-dependencies.html');

        $template->setVar(
            [
                'summary' => count($dependencies) === 1 ? '1 bundled dependency' : count($dependencies) . ' bundled dependencies',
                'items'   => rtrim($items),
            ],
        );

        return $template->render();
    }
}
