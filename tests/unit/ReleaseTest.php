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

use function date_default_timezone_get;
use function date_default_timezone_set;
use function str_repeat;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;

#[Small]
#[CoversClass(Release::class)]
final class ReleaseTest extends TestCase
{
    private string $timezone;

    public function testHasPackage(): void
    {
        $this->assertSame('package', $this->release()->package());
    }

    public function testHasVersion(): void
    {
        $this->assertSame('1.2.3', $this->release()->version());
    }

    public function testHasMajorVersion(): void
    {
        $this->assertSame('1', $this->release()->majorVersion());
    }

    public function testHasMinorVersion(): void
    {
        $this->assertSame('1.2', $this->release()->minorVersion());
    }

    public function testCanBeRepresentedAsString(): void
    {
        $this->assertSame('package-1.2.3', $this->release()->asString());
    }

    public function testHasTimestamp(): void
    {
        $this->assertSame(1772323200, $this->release()->timestamp());
    }

    public function testHasDate(): void
    {
        $this->assertSame('2026-03-01T00:00:00+00:00', $this->release()->date());
    }

    public function testHasHumanReadableSize(): void
    {
        $this->assertSame('3.22K', $this->release()->size());
    }

    public function testHasSizeInBytes(): void
    {
        $this->assertSame(3300, $this->release()->bytes());
    }

    public function testHasSha256Hash(): void
    {
        $this->assertSame(str_repeat('a', 64), $this->release()->sha256());
    }

    public function testMayHaveSbom(): void
    {
        $this->assertTrue($this->release(hasSbom: true)->hasSbom());
    }

    public function testMayNotHaveSbom(): void
    {
        $this->assertFalse($this->release(hasSbom: false)->hasSbom());
    }

    public function testMayHaveBundledDependencies(): void
    {
        $this->assertSame(
            ['example/dependency' => '1.0.0'],
            $this->release(bundledDependencies: ['example/dependency' => '1.0.0'])->bundledDependencies(),
        );
    }

    public function testMayNotHaveBundledDependencies(): void
    {
        $this->assertSame([], $this->release()->bundledDependencies());
    }

    #[Before]
    protected function setTimezone(): void
    {
        $this->timezone = date_default_timezone_get();

        date_default_timezone_set('UTC');
    }

    #[After]
    protected function restoreTimezone(): void
    {
        date_default_timezone_set($this->timezone);
    }

    /**
     * @param array<non-empty-string, non-empty-string> $bundledDependencies
     */
    private function release(bool $hasSbom = false, array $bundledDependencies = []): Release
    {
        return new Release(
            'package',
            '1.2.3',
            '1',
            '1.2',
            1772323200,
            '3.22K',
            3300,
            str_repeat('a', 64),
            $hasSbom,
            $bundledDependencies,
        );
    }
}
