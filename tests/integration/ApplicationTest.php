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
use function copy;
use function glob;
use function is_dir;
use function is_file;
use function mkdir;
use function rmdir;
use function rtrim;
use function unlink;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\Large;
use PHPUnit\Framework\TestCase;

#[Large]
final class ApplicationTest extends TestCase
{
    public function testGeneratesSite(): void
    {
        $application = new Application;

        $application->generate(
            new Arguments(
                __DIR__ . '/../fixture/configuration.xml',
                false,
                false,
            ),
        );

        $this->assertFileEquals(__DIR__ . '/../../src/assets/css/bootstrap.min.css', '/tmp/phar.example.org/public/css/bootstrap.min.css');
        $this->assertFileEquals(__DIR__ . '/../../src/assets/css/style.css', '/tmp/phar.example.org/public/css/style.css');
        $this->assertFileEquals(__DIR__ . '/../../src/assets/fonts/OpenSans.ttf', '/tmp/phar.example.org/public/fonts/OpenSans.ttf');
        $this->assertFileEquals(__DIR__ . '/../../src/assets/fonts/SourceCodePro.ttf', '/tmp/phar.example.org/public/fonts/SourceCodePro.ttf');
        $this->assertFileMatchesFormatFile(__DIR__ . '/../expectations/public/index.html', '/tmp/phar.example.org/public/index.html');
        $this->assertFileEquals(__DIR__ . '/../expectations/nginx-redirects.conf', '/tmp/phar.example.org/nginx-redirects.conf');
        $this->assertFileEquals(__DIR__ . '/../expectations/public/latest-version-of/package', '/tmp/phar.example.org/public/latest-version-of/package');
        $this->assertFileEquals(__DIR__ . '/../expectations/public/latest-version-of/package-1', '/tmp/phar.example.org/public/latest-version-of/package-1');
        $this->assertFileEquals(__DIR__ . '/../expectations/public/latest-version-of/package-1.2', '/tmp/phar.example.org/public/latest-version-of/package-1.2');
        $this->assertFileEquals(__DIR__ . '/../expectations/public/latest-version-of/package-2', '/tmp/phar.example.org/public/latest-version-of/package-2');
        $this->assertFileEquals(__DIR__ . '/../expectations/public/latest-version-of/package-2.3', '/tmp/phar.example.org/public/latest-version-of/package-2.3');
        $this->assertFileEquals(__DIR__ . '/../expectations/public/phive.xml', '/tmp/phar.example.org/public/phive.xml');
        $this->assertFileMatchesFormatFile(__DIR__ . '/../expectations/public/releases.rss', '/tmp/phar.example.org/public/releases.rss');
    }

    #[Before(2)]
    #[After]
    protected function cleanUp(): void
    {
        $this->deleteDirectory('/tmp/phar.example.org');
    }

    #[Before(1)]
    protected function copyFixture(): void
    {
        $this->createDirectory('/tmp/phar.example.org/public');

        copy(__DIR__ . '/../fixture/package-1.2.3.phar', '/tmp/phar.example.org/public/package-1.2.3.phar');
        copy(__DIR__ . '/../fixture/package-1.2.3.phar.asc', '/tmp/phar.example.org/public/package-1.2.3.phar.asc');
        copy(__DIR__ . '/../fixture/package-2.3.4.phar', '/tmp/phar.example.org/public/package-2.3.4.phar');
        copy(__DIR__ . '/../fixture/package-2.3.4.phar.asc', '/tmp/phar.example.org/public/package-2.3.4.phar.asc');
    }

    /**
     * @param non-empty-string $directory
     */
    private function createDirectory(string $directory): bool
    {
        return !(!is_dir($directory) && !@mkdir($directory, 0o777, true) && !is_dir($directory));
    }

    /**
     * @param non-empty-string $directory
     */
    private function deleteDirectory(string $directory): void
    {
        if (is_file($directory)) {
            @unlink($directory);

            return;
        }

        if (!is_dir($directory)) {
            return;
        }

        $paths = glob(rtrim($directory, '/') . '/*');

        assert($paths !== false);

        foreach ($paths as $path) {
            assert($path !== '');

            $this->deleteDirectory($path);
        }

        @rmdir($directory);
    }
}
