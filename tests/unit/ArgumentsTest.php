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
#[CoversClass(Arguments::class)]
final class ArgumentsTest extends TestCase
{
    public function testMayHaveConfigurationFile(): void
    {
        $arguments = new Arguments('configuration.xml', false, false);

        $this->assertTrue($arguments->hasConfigurationFile());
        $this->assertSame('configuration.xml', $arguments->configurationFile());
    }

    public function testMayNotHaveConfigurationFile(): void
    {
        $arguments = new Arguments(null, true, false);

        $this->assertFalse($arguments->hasConfigurationFile());
    }

    public function testConfigurationFileCannotBeRetrievedWhenItIsNotSet(): void
    {
        $arguments = new Arguments(null, true, false);

        $this->expectException(RuntimeException::class);

        $arguments->configurationFile();
    }

    public function testMayRequestHelp(): void
    {
        $arguments = new Arguments(null, true, false);

        $this->assertTrue($arguments->help());
        $this->assertFalse($arguments->version());
    }

    public function testMayRequestVersion(): void
    {
        $arguments = new Arguments(null, false, true);

        $this->assertFalse($arguments->help());
        $this->assertTrue($arguments->version());
    }
}
