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

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;

#[Small]
#[CoversClass(Configuration::class)]
final class ConfigurationTest extends TestCase
{
    public function testHasDirectoryDomainAndEmail(): void
    {
        $configuration = $this->minimalConfiguration();

        $this->assertSame('/var/www/phar.example.org', $configuration->directory());
        $this->assertSame('phar.example.org', $configuration->domain());
        $this->assertSame('phar@example.org', $configuration->email());
    }

    public function testMayHaveApacheConfigurationFile(): void
    {
        $configuration = $this->completeConfiguration();

        $this->assertTrue($configuration->shouldGenerateApacheConfigurationFile());
        $this->assertSame('/etc/httpd/phar.example.org.conf', $configuration->apacheConfigurationFile());
    }

    public function testMayNotHaveApacheConfigurationFile(): void
    {
        $configuration = $this->minimalConfiguration();

        $this->assertFalse($configuration->shouldGenerateApacheConfigurationFile());

        $this->expectException(RuntimeException::class);

        $configuration->apacheConfigurationFile();
    }

    public function testMayHaveApacheConfigurationTemplateFile(): void
    {
        $configuration = $this->completeConfiguration();

        $this->assertTrue($configuration->hasApacheConfigurationTemplateFile());
        $this->assertSame('/etc/httpd/phar.example.org.template', $configuration->apacheConfigurationTemplateFile());
    }

    public function testMayNotHaveApacheConfigurationTemplateFile(): void
    {
        $configuration = $this->minimalConfiguration();

        $this->assertFalse($configuration->hasApacheConfigurationTemplateFile());

        $this->expectException(RuntimeException::class);

        $configuration->apacheConfigurationTemplateFile();
    }

    public function testMayHaveNginxConfigurationFile(): void
    {
        $configuration = $this->completeConfiguration();

        $this->assertTrue($configuration->shouldGenerateNginxConfigurationFile());
        $this->assertSame('/etc/nginx/phar.example.org.conf', $configuration->nginxConfigurationFile());
    }

    public function testMayNotHaveNginxConfigurationFile(): void
    {
        $configuration = $this->minimalConfiguration();

        $this->assertFalse($configuration->shouldGenerateNginxConfigurationFile());

        $this->expectException(RuntimeException::class);

        $configuration->nginxConfigurationFile();
    }

    public function testMayHaveCacheFile(): void
    {
        $configuration = $this->completeConfiguration();

        $this->assertTrue($configuration->hasCacheFile());
        $this->assertSame('/var/cache/phar.example.org.json', $configuration->cacheFile());
    }

    public function testMayNotHaveCacheFile(): void
    {
        $configuration = $this->minimalConfiguration();

        $this->assertFalse($configuration->hasCacheFile());

        $this->expectException(RuntimeException::class);

        $configuration->cacheFile();
    }

    public function testMayHavePackagesThatAreIgnoredInLatestReleases(): void
    {
        $this->assertSame(['package', 'other-package'], $this->completeConfiguration()->packagesIgnoredInLatestReleases());
    }

    public function testMayNotHavePackagesThatAreIgnoredInLatestReleases(): void
    {
        $this->assertSame([], $this->minimalConfiguration()->packagesIgnoredInLatestReleases());
    }

    private function minimalConfiguration(): Configuration
    {
        return new Configuration(
            '/var/www/phar.example.org',
            'phar.example.org',
            'phar@example.org',
            null,
            null,
            null,
            null,
            [],
        );
    }

    private function completeConfiguration(): Configuration
    {
        return new Configuration(
            '/var/www/phar.example.org',
            'phar.example.org',
            'phar@example.org',
            '/etc/httpd/phar.example.org.conf',
            '/etc/httpd/phar.example.org.template',
            '/etc/nginx/phar.example.org.conf',
            '/var/cache/phar.example.org.json',
            ['package', 'other-package'],
        );
    }
}
