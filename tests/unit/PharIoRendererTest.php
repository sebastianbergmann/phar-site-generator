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
#[CoversClass(PharIoRenderer::class)]
#[CoversClass(AbstractRenderer::class)]
#[UsesClass(Release::class)]
#[UsesClass(ReleaseCollection::class)]
final class PharIoRendererTest extends TestCase
{
    private const string SHA256_OF_PACKAGE_1_2_3 = 'a72427688cadc3d3a299cc6658839011bdcbb7337493cf903d22c14864791ab1';
    private const string SHA256_OF_PACKAGE_2_3_4 = '6362706920bb68f57582e33d0718284aeb659be8dc7e02d026836529c8fe25d1';
    private string $target;

    public function testRendersAllReleasesOfPackageWithLatestReleaseFirst(): void
    {
        $releases = new ReleaseCollection;

        $releases->add(new Release('package', '2.3.4', '2', '2.3', 1772323200, '545.00B', 545, self::SHA256_OF_PACKAGE_2_3_4, true, []));
        $releases->add(new Release('package', '1.2.3', '1', '1.2', 1769904000, '3.22K', 3300, self::SHA256_OF_PACKAGE_1_2_3, false, []));

        $this->renderer()->render($releases);

        $this->assertFileEquals(__DIR__ . '/../expectations/public/phive.xml', $this->target);
    }

    public function testRendersReleasesOfEachPackageSeparately(): void
    {
        $releases = new ReleaseCollection;

        $releases->add(new Release('package', '1.0.0', '1', '1.0', 1769904000, '1.00K', 1024, str_repeat('a', 64), false, []));
        $releases->add(new Release('other', '1.0.0', '1', '1.0', 1769904000, '1.00K', 1024, str_repeat('b', 64), false, []));

        $this->renderer()->render($releases);

        $this->assertStringEqualsFile(
            $this->target,
            <<<'EOT'
<?xml version="1.0"?>
<repository xmlns="https://phar.io/repository" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="https://phar.io/repository https://phar.io/data/repository.xsd">
  <phar name="other">
    <release version="1.0.0" url="https://phar.example.org/other-1.0.0.phar">
      <signature type="gpg"/>
      <hash type="sha-256" value="bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb"/>
    </release>
  </phar>
  <phar name="package">
    <release version="1.0.0" url="https://phar.example.org/package-1.0.0.phar">
      <signature type="gpg"/>
      <hash type="sha-256" value="aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa"/>
    </release>
  </phar>
</repository>

EOT,
        );
    }

    public function testRendersEmptyRepositoryWhenThereAreNoReleases(): void
    {
        $this->renderer()->render(new ReleaseCollection);

        $this->assertStringEqualsFile(
            $this->target,
            <<<'EOT'
<?xml version="1.0"?>
<repository xmlns="https://phar.io/repository" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="https://phar.io/repository https://phar.io/data/repository.xsd"/>

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

    private function renderer(): PharIoRenderer
    {
        assert($this->target !== '');

        return new PharIoRenderer($this->target, 'phar.example.org', 'phar@example.org');
    }
}
