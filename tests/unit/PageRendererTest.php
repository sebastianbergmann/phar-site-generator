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

use const DIRECTORY_SEPARATOR;
use const JSON_THROW_ON_ERROR;
use function assert;
use function bin2hex;
use function count;
use function date_default_timezone_get;
use function date_default_timezone_set;
use function file_get_contents;
use function glob;
use function is_array;
use function json_decode;
use function mkdir;
use function preg_match;
use function preg_match_all;
use function random_bytes;
use function rmdir;
use function str_repeat;
use function strpos;
use function substr;
use function sys_get_temp_dir;
use function unlink;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Medium;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[Medium]
#[CoversClass(PageRenderer::class)]
#[CoversClass(AbstractRenderer::class)]
#[UsesClass(Release::class)]
#[UsesClass(ReleaseCollection::class)]
final class PageRendererTest extends TestCase
{
    private const string SHA256_OF_PACKAGE_1_2_3 = 'a72427688cadc3d3a299cc6658839011bdcbb7337493cf903d22c14864791ab1';
    private const string SHA256_OF_PACKAGE_2_3_4 = '6362706920bb68f57582e33d0718284aeb659be8dc7e02d026836529c8fe25d1';
    private string $directory;
    private string $timezone;

    /**
     * @return non-empty-array<non-empty-string, array{0: list<non-empty-string>, 1: non-empty-string}>
     */
    public static function captionProvider(): array
    {
        return [
            'no package is ignored' => [
                [],
                'The most recent release of each PHAR distributed on phar.example.org',
            ],
            'package that has no release is ignored' => [
                ['unknown'],
                'The most recent release of each PHAR distributed on phar.example.org',
            ],
            'one package is ignored' => [
                ['b'],
                'The most recent release of each PHAR distributed on phar.example.org, except for b',
            ],
            'two packages are ignored' => [
                ['c', 'a'],
                'The most recent release of each PHAR distributed on phar.example.org, except for a and c',
            ],
            'three packages are ignored' => [
                ['c', 'b', 'a'],
                'The most recent release of each PHAR distributed on phar.example.org, except for a, b, and c',
            ],
        ];
    }

    public function testRendersPageThatListsLatestReleases(): void
    {
        $this->renderer()->render($this->releases());

        $this->assertFileMatchesFormatFile(__DIR__ . '/../expectations/public/index.html', $this->directory . '/index.html');
    }

    public function testRendersPageThatListsAllReleases(): void
    {
        $this->renderer()->render($this->releases());

        $this->assertFileMatchesFormatFile(__DIR__ . '/../expectations/public/all-releases.html', $this->directory . '/all-releases.html');
    }

    public function testListsLatestReleasesSortedByDateInDescendingOrder(): void
    {
        $releases = new ReleaseCollection;

        $releases->add($this->release('a', 1769904000));
        $releases->add($this->release('b', 1772323200));
        $releases->add($this->release('c', 1767225600));

        $this->renderer()->render($releases);

        $this->assertSame(['b-1.0.0.phar', 'a-1.0.0.phar', 'c-1.0.0.phar'], $this->pharsListedInLatestReleases());
    }

    public function testDoesNotListIgnoredPackagesInLatestReleases(): void
    {
        $releases = new ReleaseCollection;

        $releases->add($this->release('a', 1769904000));
        $releases->add($this->release('b', 1772323200));
        $releases->add($this->release('c', 1767225600));

        $this->renderer(['b'])->render($releases);

        $this->assertSame(['a-1.0.0.phar', 'c-1.0.0.phar'], $this->pharsListedInLatestReleases());
    }

    /**
     * @param list<non-empty-string> $packagesIgnoredInLatestReleases
     */
    #[DataProvider('captionProvider')]
    public function testNamesIgnoredPackagesInCaptionOfLatestReleases(array $packagesIgnoredInLatestReleases, string $expectedCaption): void
    {
        $releases = new ReleaseCollection;

        $releases->add($this->release('a', 1769904000));
        $releases->add($this->release('b', 1772323200));
        $releases->add($this->release('c', 1767225600));
        $releases->add($this->release('d', 1767225600));

        $this->renderer($packagesIgnoredInLatestReleases)->render($releases);

        $this->assertSame($expectedCaption, $this->captionOfLatestReleases());
    }

    public function testEscapesIgnoredPackagesInCaptionOfLatestReleases(): void
    {
        $releases = new ReleaseCollection;

        $releases->add($this->release('a&b', 1769904000));
        $releases->add($this->release('c', 1769904000));

        $this->renderer(['a&b'])->render($releases);

        $this->assertSame(
            'The most recent release of each PHAR distributed on phar.example.org, except for a&amp;b',
            $this->captionOfLatestReleases(),
        );
    }

    public function testDescribesLatestReleasesUsingStructuredData(): void
    {
        $releases = new ReleaseCollection;

        $releases->add($this->release('a', 1769904000));
        $releases->add($this->release('b', 1772323200));

        $this->renderer()->render($releases);

        $this->assertSame(
            $this->expectedStructuredData(
                $this->structuredDataItem(1, 'b', '2026-03-01T00:00:00+00:00'),
                $this->structuredDataItem(2, 'a', '2026-02-01T00:00:00+00:00'),
            ),
            $this->structuredData('index.html'),
        );

        $this->assertSame($this->structuredData('index.html'), $this->structuredData('all-releases.html'));
    }

    public function testDoesNotDescribeIgnoredPackagesUsingStructuredData(): void
    {
        $releases = new ReleaseCollection;

        $releases->add($this->release('a', 1769904000));
        $releases->add($this->release('b', 1772323200));

        $this->renderer(['b'])->render($releases);

        $this->assertSame(
            $this->expectedStructuredData(
                $this->structuredDataItem(1, 'a', '2026-02-01T00:00:00+00:00'),
            ),
            $this->structuredData('index.html'),
        );
    }

    public function testRendersSingleBundledDependency(): void
    {
        $releases = new ReleaseCollection;

        $releases->add($this->release('a', 1769904000, ['example/dependency' => '1.0.0']));

        $this->renderer()->render($releases);

        $this->assertStringContainsString(
            <<<'EOT'
       <details>
        <summary>1 bundled dependency</summary>
        <ul>
         <li>example/dependency: 1.0.0</li>
        </ul>
       </details>
EOT,
            $this->renderedPage('index.html'),
        );
    }

    public function testEscapesBundledDependencies(): void
    {
        $releases = new ReleaseCollection;

        $releases->add($this->release('a', 1769904000, ['example/<dependency>' => '1.0.0&"dev"']));

        $this->renderer()->render($releases);

        $this->assertStringContainsString('<li>example/&lt;dependency&gt;: 1.0.0&amp;&quot;dev&quot;</li>', $this->renderedPage('index.html'));
    }

    #[Before]
    protected function createDirectory(): void
    {
        $this->directory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'phar-site-generator-' . bin2hex(random_bytes(8));

        mkdir($this->directory);
    }

    #[After]
    protected function deleteDirectory(): void
    {
        $files = glob($this->directory . '/*');

        assert($files !== false);

        foreach ($files as $file) {
            unlink($file);
        }

        rmdir($this->directory);
    }

    #[Before]
    protected function setTimezone(): void
    {
        $this->timezone = date_default_timezone_get();

        date_default_timezone_set('UTC');
    }

    #[After]
    protected function restoreTimezone(): void
    {
        date_default_timezone_set($this->timezone);
    }

    /**
     * @param list<non-empty-string> $packagesIgnoredInLatestReleases
     */
    private function renderer(array $packagesIgnoredInLatestReleases = []): PageRenderer
    {
        return new PageRenderer(
            $this->directory . DIRECTORY_SEPARATOR,
            'phar.example.org',
            'phar@example.org',
            $packagesIgnoredInLatestReleases,
        );
    }

    private function releases(): ReleaseCollection
    {
        $releases = new ReleaseCollection;

        $releases->add(
            new Release(
                'package',
                '1.2.3',
                '1',
                '1.2',
                1769904000,
                '3.22K',
                3300,
                self::SHA256_OF_PACKAGE_1_2_3,
                false,
                [],
            ),
        );

        $releases->add(
            new Release(
                'package',
                '2.3.4',
                '2',
                '2.3',
                1772323200,
                '545.00B',
                545,
                self::SHA256_OF_PACKAGE_2_3_4,
                true,
                [
                    'example/dependency'         => '1.0.0',
                    'example/another-dependency' => '2.0.0@3d8e0fbe5b2fb2e4d36c4d5be6e7ad0b6c3fd2d1',
                ],
            ),
        );

        return $releases;
    }

    /**
     * @param non-empty-string                          $package
     * @param array<non-empty-string, non-empty-string> $bundledDependencies
     */
    private function release(string $package, int $timestamp, array $bundledDependencies = []): Release
    {
        return new Release(
            $package,
            '1.0.0',
            '1',
            '1.0',
            $timestamp,
            '1.00K',
            1024,
            str_repeat('a', 64),
            false,
            $bundledDependencies,
        );
    }

    /**
     * @param array<string, mixed> ...$items
     *
     * @return array<string, mixed>
     */
    private function expectedStructuredData(array ...$items): array
    {
        return [
            '@context' => 'https://schema.org',
            '@graph'   => [
                [
                    '@type'       => 'WebSite',
                    '@id'         => 'https://phar.example.org/#website',
                    'url'         => 'https://phar.example.org/',
                    'name'        => 'phar.example.org',
                    'description' => 'Download PHP Archives (PHAR) from phar.example.org. Every release is listed with its file size, last modification date, SHA-256 checksum, and detached OpenPGP signature.',
                ],
                [
                    '@type'           => 'ItemList',
                    'name'            => 'Latest releases on phar.example.org',
                    'numberOfItems'   => count($items),
                    'itemListElement' => $items,
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function structuredDataItem(int $position, string $package, string $date): array
    {
        return [
            '@type'    => 'ListItem',
            'position' => $position,
            'item'     => [
                '@type'               => 'SoftwareApplication',
                'name'                => $package,
                'softwareVersion'     => '1.0.0',
                'applicationCategory' => 'DeveloperApplication',
                'operatingSystem'     => 'Any',
                'dateModified'        => $date,
                'downloadUrl'         => 'https://phar.example.org/' . $package . '-1.0.0.phar',
                'associatedMedia'     => [
                    '@type'       => 'DataDownload',
                    'contentUrl'  => 'https://phar.example.org/' . $package . '-1.0.0.phar',
                    'contentSize' => '1024',
                    'sha256'      => str_repeat('a', 64),
                ],
            ],
        ];
    }

    private function renderedPage(string $file): string
    {
        $page = file_get_contents($this->directory . DIRECTORY_SEPARATOR . $file);

        assert($page !== false);

        return $page;
    }

    /**
     * @return list<string>
     */
    private function pharsListedInLatestReleases(): array
    {
        $page  = $this->renderedPage('index.html');
        $start = strpos($page, '<h2 id="latest-releases">');
        $end   = strpos($page, '<h2 id="all-releases">');

        assert($start !== false && $end !== false);

        preg_match_all('#<a href="https://phar\.example\.org/([^"/]+\.phar)">#', substr($page, $start, $end - $start), $matches);

        return $matches[1];
    }

    private function captionOfLatestReleases(): string
    {
        $this->assertSame(1, preg_match('#<caption>(.*)</caption>#', $this->renderedPage('index.html'), $matches));

        return $matches[1];
    }

    /**
     * @return array<mixed>
     */
    private function structuredData(string $file): array
    {
        $this->assertSame(1, preg_match('#<script type="application/ld\+json">\n(.*)\n  </script>#s', $this->renderedPage($file), $matches));

        $structuredData = json_decode($matches[1], true, flags: JSON_THROW_ON_ERROR);

        assert(is_array($structuredData));

        return $structuredData;
    }
}
