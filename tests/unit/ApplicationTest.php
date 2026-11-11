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
use function copy;
use function file_get_contents;
use function file_put_contents;
use function glob;
use function is_dir;
use function mkdir;
use function random_bytes;
use function rmdir;
use function sys_get_temp_dir;
use function unlink;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Medium;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[Medium]
#[CoversClass(Application::class)]
#[UsesClass(AbstractRenderer::class)]
#[UsesClass(ApacheConfigRenderer::class)]
#[UsesClass(Arguments::class)]
#[UsesClass(ArgumentsBuilder::class)]
#[UsesClass(Cache::class)]
#[UsesClass(Configuration::class)]
#[UsesClass(ConfigurationLoader::class)]
#[UsesClass(FeedRenderer::class)]
#[UsesClass(MetaDataRenderer::class)]
#[UsesClass(NginxConfigRenderer::class)]
#[UsesClass(PageRenderer::class)]
#[UsesClass(PharIoRenderer::class)]
#[UsesClass(PharReader::class)]
#[UsesClass(Release::class)]
#[UsesClass(ReleaseCollection::class)]
#[UsesClass(ReleaseCollector::class)]
final class ApplicationTest extends TestCase
{
    private string $directory;

    public function testPrintsVersion(): void
    {
        $this->expectOutputRegex('/\Aphar-site-generator \S+ by Sebastian Bergmann\.\n\z/');

        $this->assertSame(0, (new Application)->run(['phar-site-generator', '--version']));
    }

    public function testPrintsUsage(): void
    {
        $this->expectOutputRegex('/\Aphar-site-generator \S+ by Sebastian Bergmann\.\n\nUsage:\n  phar-site-generator <configuration file>\n\z/');

        $this->assertSame(0, (new Application)->run(['phar-site-generator', '--help']));
    }

    public function testPrintsErrorWhenNoConfigurationFileIsSpecified(): void
    {
        $this->expectOutputRegex('/\Aphar-site-generator \S+ by Sebastian Bergmann\.\n\nNo configurationFile specified\n\z/');

        $this->assertSame(1, (new Application)->run(['phar-site-generator']));
    }

    public function testPrintsErrorWhenUnknownOptionIsSpecified(): void
    {
        $this->expectOutputRegex('/\Aphar-site-generator \S+ by Sebastian Bergmann\.\n\nUnknown option "-x"\n\z/');

        $this->assertSame(1, (new Application)->run(['phar-site-generator', '-x', 'configuration.xml']));
    }

    public function testPrintsErrorWhenConfigurationFileCannotBeRead(): void
    {
        $this->expectOutputRegex('#\Aphar-site-generator \S+ by Sebastian Bergmann\.\n\nConfiguration file "/does/not/exist\.xml" could not be read or is empty\n\z#');

        $this->assertSame(1, (new Application)->run(['phar-site-generator', '/does/not/exist.xml']));
    }

    public function testPrintsSummaryAfterSiteWasGenerated(): void
    {
        $this->copyPhar('package-1.2.3.phar');

        $this->expectOutputRegex('/\Aphar-site-generator \S+ by Sebastian Bergmann\.\n\n1 release processed\n\z/');

        $this->assertSame(0, (new Application)->run(['phar-site-generator', $this->configurationFile()]));
    }

    public function testGeneratesSite(): void
    {
        $this->copyPhar('package-1.2.3.phar');
        $this->copyPhar('package-2.3.4.phar');

        $this->assertSame('2 releases processed', $this->generate($this->configurationFile()));

        $this->assertFileExists($this->directory . '/public/index.html');
        $this->assertFileExists($this->directory . '/public/all-releases.html');
        $this->assertFileExists($this->directory . '/public/releases.rss');
        $this->assertFileExists($this->directory . '/public/phive.xml');
        $this->assertStringEqualsFile($this->directory . '/public/latest-version-of/package', '2.3.4');
        $this->assertFileEquals(__DIR__ . '/../../src/assets/css/style.css', $this->directory . '/public/css/style.css');
        $this->assertFileEquals(__DIR__ . '/../../src/assets/fonts/OpenSans.ttf', $this->directory . '/public/fonts/OpenSans.ttf');
        $this->assertFileEquals(__DIR__ . '/../../src/assets/fonts/SourceCodePro.ttf', $this->directory . '/public/fonts/SourceCodePro.ttf');
    }

    public function testCreatesDirectoryForSiteWhenItDoesNotExist(): void
    {
        rmdir($this->directory . '/public');

        $this->assertSame('0 releases processed', $this->generate($this->configurationFile()));

        $this->assertFileExists($this->directory . '/public/index.html');
    }

    public function testGeneratesApacheConfiguration(): void
    {
        $this->copyPhar('package-1.2.3.phar');

        $this->generate($this->configurationFile('<apache>' . $this->directory . '/apache.conf</apache>'));

        $this->assertStringContainsString('Redirect "/package.phar" "/package-1.2.3.phar"', $this->contents('apache.conf'));
    }

    public function testGeneratesApacheConfigurationUsingTemplate(): void
    {
        $this->copyPhar('package-1.2.3.phar');
        file_put_contents($this->directory . '/apache.template', "# Template\n{configuration}");

        $this->generate(
            $this->configurationFile(
                '<apache template="' . $this->directory . '/apache.template">' . $this->directory . '/apache.conf</apache>',
            ),
        );

        $this->assertStringStartsWith("# Template\nAddType application/octet-stream .phar\n", $this->contents('apache.conf'));
    }

    public function testGeneratesNginxConfiguration(): void
    {
        $this->copyPhar('package-1.2.3.phar');

        $this->generate($this->configurationFile('<nginx>' . $this->directory . '/nginx.conf</nginx>'));

        $this->assertStringContainsString('rewrite ^/package.phar$ /package-1.2.3.phar redirect;', $this->contents('nginx.conf'));
    }

    public function testReportsCacheHitsAndMissesWhenCacheIsConfigured(): void
    {
        $configurationFile = $this->configurationFile('<cache>' . $this->directory . '/cache.json</cache>');

        $this->copyPhar('package-1.2.3.phar');

        $this->assertSame('1 release processed, 0 cache hits, 1 cache miss', $this->generate($configurationFile));
        $this->assertFileExists($this->directory . '/cache.json');

        $this->copyPhar('package-2.3.4.phar');

        $this->assertSame('2 releases processed, 1 cache hit, 1 cache miss', $this->generate($configurationFile));
    }

    public function testCannotGenerateSiteWhenDirectoryCannotBeCreated(): void
    {
        file_put_contents($this->directory . '/public/latest-version-of', '');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageIs('Directory "' . $this->directory . '/public/latest-version-of" does not exist.');

        $this->generate($this->configurationFile());
    }

    #[Before]
    protected function createDirectory(): void
    {
        $this->directory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'phar-site-generator-' . bin2hex(random_bytes(8));

        mkdir($this->directory . '/public', 0o777, true);
    }

    #[After]
    protected function deleteDirectory(): void
    {
        $this->delete($this->directory);
    }

    /**
     * @param non-empty-string $configurationFile
     */
    private function generate(string $configurationFile): string
    {
        return (new Application)->generate(new Arguments($configurationFile, false, false));
    }

    /**
     * @return non-empty-string
     */
    private function configurationFile(string $additionalConfiguration = ''): string
    {
        $configurationFile = $this->directory . '/configuration.xml';

        file_put_contents(
            $configurationFile,
            <<<EOT
<?xml version="1.0" encoding="UTF-8"?>
<phar-site>
    <domain>phar.example.org</domain>
    <email>phar@example.org</email>
    <directory>{$this->directory}/public</directory>
    {$additionalConfiguration}
</phar-site>
EOT,
        );

        return $configurationFile;
    }

    private function copyPhar(string $phar): void
    {
        copy(__DIR__ . '/../fixture/' . $phar, $this->directory . '/public/' . $phar);
    }

    private function contents(string $file): string
    {
        $contents = file_get_contents($this->directory . DIRECTORY_SEPARATOR . $file);

        assert($contents !== false);

        return $contents;
    }

    private function delete(string $path): void
    {
        if (!is_dir($path)) {
            unlink($path);

            return;
        }

        $paths = glob($path . '/*');

        assert($paths !== false);

        foreach ($paths as $child) {
            $this->delete($child);
        }

        rmdir($path);
    }
}
