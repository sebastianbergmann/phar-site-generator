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
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[Small]
#[CoversClass(ArgumentsBuilder::class)]
#[UsesClass(Arguments::class)]
final class ArgumentsBuilderTest extends TestCase
{
    public function testBuildsArgumentsWithConfigurationFile(): void
    {
        $arguments = (new ArgumentsBuilder)->build(['phar-site-generator', 'configuration.xml']);

        $this->assertTrue($arguments->hasConfigurationFile());
        $this->assertSame('configuration.xml', $arguments->configurationFile());
        $this->assertFalse($arguments->help());
        $this->assertFalse($arguments->version());
    }

    public function testTrimsConfigurationFile(): void
    {
        $arguments = (new ArgumentsBuilder)->build(['phar-site-generator', ' configuration.xml ']);

        $this->assertSame('configuration.xml', $arguments->configurationFile());
    }

    #[TestWith(['-h'])]
    #[TestWith(['--help'])]
    public function testBuildsArgumentsForHelp(string $option): void
    {
        $arguments = (new ArgumentsBuilder)->build(['phar-site-generator', $option]);

        $this->assertFalse($arguments->hasConfigurationFile());
        $this->assertTrue($arguments->help());
        $this->assertFalse($arguments->version());
    }

    #[TestWith(['-v'])]
    #[TestWith(['--version'])]
    public function testBuildsArgumentsForVersion(string $option): void
    {
        $arguments = (new ArgumentsBuilder)->build(['phar-site-generator', $option]);

        $this->assertFalse($arguments->hasConfigurationFile());
        $this->assertFalse($arguments->help());
        $this->assertTrue($arguments->version());
    }

    public function testBuildsArgumentsWithOptionAndConfigurationFile(): void
    {
        $arguments = (new ArgumentsBuilder)->build(['phar-site-generator', '--help', 'configuration.xml']);

        $this->assertSame('configuration.xml', $arguments->configurationFile());
        $this->assertTrue($arguments->help());
    }

    public function testCannotBuildArgumentsWithoutConfigurationFile(): void
    {
        $this->expectException(ArgumentsBuilderException::class);
        $this->expectExceptionMessageIs('No configurationFile specified');

        (new ArgumentsBuilder)->build(['phar-site-generator']);
    }

    public function testCannotBuildArgumentsWithEmptyConfigurationFile(): void
    {
        $this->expectException(ArgumentsBuilderException::class);
        $this->expectExceptionMessageIs('No configurationFile specified');

        (new ArgumentsBuilder)->build(['phar-site-generator', ' ']);
    }

    public function testCannotBuildArgumentsWithUnknownOption(): void
    {
        $this->expectException(ArgumentsBuilderException::class);
        $this->expectExceptionMessageIs('Unknown option "-x"');

        (new ArgumentsBuilder)->build(['phar-site-generator', '-x', 'configuration.xml']);
    }
}
