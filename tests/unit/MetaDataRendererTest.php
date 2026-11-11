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
use function array_map;
use function assert;
use function basename;
use function bin2hex;
use function glob;
use function mkdir;
use function random_bytes;
use function rmdir;
use function str_repeat;
use function sys_get_temp_dir;
use function unlink;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Medium;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[Medium]
#[CoversClass(MetaDataRenderer::class)]
#[CoversClass(AbstractRenderer::class)]
#[UsesClass(Release::class)]
#[UsesClass(ReleaseCollection::class)]
final class MetaDataRendererTest extends TestCase
{
    private string $directory;

    public function testRendersVersionOfLatestReleaseOfEachPackageAndOfEachMajorAndMinorVersion(): void
    {
        $releases = new ReleaseCollection;

        $releases->add(new Release('package', '1.2.3', '1', '1.2', 1769904000, '3.22K', 3300, str_repeat('a', 64), false, []));
        $releases->add(new Release('package', '2.3.4', '2', '2.3', 1772323200, '545.00B', 545, str_repeat('b', 64), true, []));

        $this->renderer()->render($releases);

        $this->assertSame(['package', 'package-1', 'package-1.2', 'package-2', 'package-2.3'], $this->renderedFiles());

        foreach ($this->renderedFiles() as $file) {
            $this->assertFileEquals(
                __DIR__ . '/../expectations/public/latest-version-of/' . $file,
                $this->directory . DIRECTORY_SEPARATOR . $file,
            );
        }
    }

    public function testRendersNothingWhenThereAreNoReleases(): void
    {
        $this->renderer()->render(new ReleaseCollection);

        $this->assertSame([], $this->renderedFiles());
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

    private function renderer(): MetaDataRenderer
    {
        return new MetaDataRenderer($this->directory . DIRECTORY_SEPARATOR, 'phar.example.org', 'phar@example.org');
    }

    /**
     * @return list<string>
     */
    private function renderedFiles(): array
    {
        $files = glob($this->directory . '/*');

        assert($files !== false);

        return array_map(basename(...), $files);
    }
}
