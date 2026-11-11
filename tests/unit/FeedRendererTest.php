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

use function assert;
use function date_default_timezone_get;
use function date_default_timezone_set;
use function str_repeat;
use function sys_get_temp_dir;
use function tempnam;
use function unlink;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Medium;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[Medium]
#[CoversClass(FeedRenderer::class)]
#[CoversClass(AbstractRenderer::class)]
#[UsesClass(Release::class)]
#[UsesClass(ReleaseCollection::class)]
final class FeedRendererTest extends TestCase
{
    private string $target;
    private string $timezone;

    public function testRendersLatestReleaseOfEachPackageSortedByDate(): void
    {
        $releases = new ReleaseCollection;

        $releases->add(new Release('package', '1.2.3', '1', '1.2', 1767225600, '3.22K', 3300, str_repeat('a', 64), false, []));
        $releases->add(new Release('package', '2.3.4', '2', '2.3', 1772323200, '545.00B', 545, str_repeat('b', 64), true, []));
        $releases->add(new Release('other', '1.0.0', '1', '1.0', 1769904000, '1.00K', 1024, str_repeat('c', 64), false, []));

        $this->renderer()->render($releases);

        $this->assertStringEqualsFile(
            $this->target,
            <<<'EOT'
<?xml version="1.0" encoding="utf-8"?>
<rdf:RDF xmlns="http://purl.org/rss/1.0/"
         xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
         xmlns:content="http://purl.org/rss/1.0/modules/content/"
         xmlns:dc="http://purl.org/dc/elements/1.1/">
 <channel rdf:about="https://phar.example.org/">
  <link>https://phar.example.org/</link>
  <dc:creator>phar@example.org</dc:creator>
  <dc:publisher>phar@example.org</dc:publisher>
  <dc:language>en-us</dc:language>
  <items>
   <rdf:Seq>
    <rdf:li rdf:resource="https://phar.example.org/other-1.0.0.phar"/>
    <rdf:li rdf:resource="https://phar.example.org/package-2.3.4.phar"/>
   </rdf:Seq>
  </items>
  <title>Latest Releases on phar.example.org</title>
  <description>The latest releases on phar.example.org.</description>
 </channel>
 <item rdf:about="https://phar.example.org/other-1.0.0.phar">
  <title>other 1.0.0</title>
  <link>https://phar.example.org/other-1.0.0.phar</link>
  <content:encoded></content:encoded>
  <dc:date>2026-02-01T00:00:00+00:00</dc:date>
 </item>
 <item rdf:about="https://phar.example.org/package-2.3.4.phar">
  <title>package 2.3.4</title>
  <link>https://phar.example.org/package-2.3.4.phar</link>
  <content:encoded></content:encoded>
  <dc:date>2026-03-01T00:00:00+00:00</dc:date>
 </item>
</rdf:RDF>


EOT,
        );
    }

    public function testRendersFeedWithoutItemsWhenThereAreNoReleases(): void
    {
        $this->renderer()->render(new ReleaseCollection);

        $this->assertStringEqualsFile(
            $this->target,
            <<<'EOT'
<?xml version="1.0" encoding="utf-8"?>
<rdf:RDF xmlns="http://purl.org/rss/1.0/"
         xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
         xmlns:content="http://purl.org/rss/1.0/modules/content/"
         xmlns:dc="http://purl.org/dc/elements/1.1/">
 <channel rdf:about="https://phar.example.org/">
  <link>https://phar.example.org/</link>
  <dc:creator>phar@example.org</dc:creator>
  <dc:publisher>phar@example.org</dc:publisher>
  <dc:language>en-us</dc:language>
  <items>
   <rdf:Seq>
   </rdf:Seq>
  </items>
  <title>Latest Releases on phar.example.org</title>
  <description>The latest releases on phar.example.org.</description>
 </channel>
</rdf:RDF>


EOT,
        );
    }

    #[Before]
    protected function createTarget(): void
    {
        $target = tempnam(sys_get_temp_dir(), 'phar-site-generator-');

        assert($target !== false);

        $this->target = $target;
    }

    #[After]
    protected function deleteTarget(): void
    {
        unlink($this->target);
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

    private function renderer(): FeedRenderer
    {
        assert($this->target !== '');

        return new FeedRenderer($this->target, 'phar.example.org', 'phar@example.org');
    }
}
