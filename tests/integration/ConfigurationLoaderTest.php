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

use function getenv;
use function putenv;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;

#[Small]
final class ConfigurationLoaderTest extends TestCase
{
    private false|string $home;

    public function testLoadsConfiguration(): void
    {
        $configuration = (new ConfigurationLoader)->load(__DIR__ . '/../fixture/configuration.xml');

        $this->assertSame('/tmp/phar.example.org/public', $configuration->directory());
        $this->assertSame('phar.example.org', $configuration->domain());
        $this->assertSame('phar@example.org', $configuration->email());
        $this->assertSame('/tmp/phar.example.org/apache-redirects.conf', $configuration->apacheConfigurationFile());
        $this->assertSame('/tmp/phar.example.org/nginx-redirects.conf', $configuration->nginxConfigurationFile());
        $this->assertSame('/tmp/phar.example.org/cache.json', $configuration->cacheFile());
    }

    public function testExpandsLeadingTildeInPathsToHomeDirectory(): void
    {
        putenv('HOME=/home/example');

        $configuration = (new ConfigurationLoader)->load(__DIR__ . '/../fixture/configuration-with-tilde.xml');

        $this->assertSame('/home/example/public', $configuration->directory());
        $this->assertSame('/home/example/apache-redirects.conf', $configuration->apacheConfigurationFile());
        $this->assertSame('/home/example/nginx-redirects.conf', $configuration->nginxConfigurationFile());
        $this->assertSame('/home/example/cache.json', $configuration->cacheFile());
    }

    public function testCannotExpandLeadingTildeInPathsWhenHomeDirectoryIsUnknown(): void
    {
        putenv('HOME');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('cannot be expanded because the HOME environment variable is not set');

        (new ConfigurationLoader)->load(__DIR__ . '/../fixture/configuration-with-tilde.xml');
    }

    #[Before]
    protected function backupHome(): void
    {
        $this->home = getenv('HOME');
    }

    #[After]
    protected function restoreHome(): void
    {
        if ($this->home === false) {
            putenv('HOME');

            return;
        }

        putenv('HOME=' . $this->home);
    }
}
