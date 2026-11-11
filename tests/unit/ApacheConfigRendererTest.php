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
use function assert;
use function bin2hex;
use function chmod;
use function clearstatcache;
use function file_put_contents;
use function fileperms;
use function glob;
use function is_dir;
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
#[CoversClass(ApacheConfigRenderer::class)]
#[UsesClass(Release::class)]
#[UsesClass(ReleaseCollection::class)]
final class ApacheConfigRendererTest extends TestCase
{
    private string $directory;

    public function testRendersMimeTypesAndRedirectsToLatestReleaseOfEachPackageAndOfEachMajorAndMinorVersion(): void
    {
        (new ApacheConfigRenderer)->render($this->releases(), $this->target());

        $this->assertFileEquals(__DIR__ . '/../expectations/apache-redirects.conf', $this->target());
    }

    public function testRendersConfigurationUsingTemplate(): void
    {
        ApacheConfigRenderer::fromTemplateFile(__DIR__ . '/../fixture/apache.template')->render($this->releases(), $this->target());

        $this->assertFileEquals(__DIR__ . '/../expectations/apache-redirects-with-template.conf', $this->target());
    }

    public function testCannotBeCreatedFromTemplateFileThatCannotBeRead(): void
    {
        $templateFile = $this->directory . '/apache.template';

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageIs('Template file "' . $templateFile . '" for the Apache HTTPD configuration could not be read');

        ApacheConfigRenderer::fromTemplateFile($templateFile);
    }

    public function testCannotBeCreatedFromTemplateFileThatDoesNotContainPlaceholder(): void
    {
        $templateFile = $this->directory . '/apache.template';

        file_put_contents($templateFile, "Options -Indexes\n");

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageIs('Template file "' . $templateFile . '" for the Apache HTTPD configuration does not contain the {configuration} placeholder');

        ApacheConfigRenderer::fromTemplateFile($templateFile);
    }

    public function testReplacesExistingConfigurationFileWithoutLeavingTemporaryFileBehind(): void
    {
        file_put_contents($this->target(), 'outdated');

        (new ApacheConfigRenderer)->render($this->releases(), $this->target());

        $this->assertFileEquals(__DIR__ . '/../expectations/apache-redirects.conf', $this->target());
        $this->assertFileDoesNotExist($this->target() . '.tmp');
    }

    public function testPreservesPermissionsOfExistingConfigurationFile(): void
    {
        file_put_contents($this->target(), 'outdated');
        chmod($this->target(), 0o640);

        (new ApacheConfigRenderer)->render($this->releases(), $this->target());

        clearstatcache();

        $this->assertSame(0o640, fileperms($this->target()) & 0o777);
    }

    public function testCannotRenderToFileThatCannotBeWritten(): void
    {
        $target = $this->directory . '/does-not-exist/apache-redirects.conf';

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageIs('Apache HTTPD configuration file "' . $target . '" could not be written: Failed to open stream: No such file or directory');

        (new ApacheConfigRenderer)->render($this->releases(), $target);
    }

    public function testCannotRenderToFileThatCannotBeReplaced(): void
    {
        mkdir($this->target());

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageIs('Apache HTTPD configuration file "' . $this->target() . '" could not be written: Is a directory');

        (new ApacheConfigRenderer)->render($this->releases(), $this->target());
    }

    public function testDoesNotLeaveTemporaryFileBehindWhenFileCannotBeReplaced(): void
    {
        mkdir($this->target());

        try {
            (new ApacheConfigRenderer)->render($this->releases(), $this->target());
        } catch (RuntimeException) {
        }

        $this->assertFileDoesNotExist($this->target() . '.tmp');
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
            is_dir($file) ? rmdir($file) : unlink($file);
        }

        rmdir($this->directory);
    }

    private function target(): string
    {
        return $this->directory . '/apache-redirects.conf';
    }

    private function releases(): ReleaseCollection
    {
        $releases = new ReleaseCollection;

        $releases->add(new Release('package', '1.2.3', '1', '1.2', 1769904000, '3.22K', 3300, str_repeat('a', 64), false, []));
        $releases->add(new Release('package', '2.3.4', '2', '2.3', 1772323200, '545.00B', 545, str_repeat('b', 64), true, []));

        return $releases;
    }
}
