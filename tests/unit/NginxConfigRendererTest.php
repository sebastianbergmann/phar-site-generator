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
#[CoversClass(NginxConfigRenderer::class)]
#[UsesClass(Release::class)]
#[UsesClass(ReleaseCollection::class)]
final class NginxConfigRendererTest extends TestCase
{
    private string $target;

    public function testRendersRedirectsToLatestReleaseOfEachPackageAndOfEachMajorAndMinorVersion(): void
    {
        (new NginxConfigRenderer)->render($this->releases(), $this->target);

        $this->assertFileEquals(__DIR__ . '/../expectations/nginx-redirects.conf', $this->target);
    }

    public function testRendersEmptyConfigurationWhenThereAreNoReleases(): void
    {
        (new NginxConfigRenderer)->render(new ReleaseCollection, $this->target);

        $this->assertStringEqualsFile($this->target, '');
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

    private function releases(): ReleaseCollection
    {
        $releases = new ReleaseCollection;

        $releases->add(new Release('package', '1.2.3', '1', '1.2', 1769904000, '3.22K', 3300, str_repeat('a', 64), false, []));
        $releases->add(new Release('package', '2.3.4', '2', '2.3', 1772323200, '545.00B', 545, str_repeat('b', 64), true, []));

        return $releases;
    }
}
