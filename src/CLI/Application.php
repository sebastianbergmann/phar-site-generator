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
use const PHP_EOL;
use function assert;
use function copy;
use function count;
use function dirname;
use function is_dir;
use function mkdir;
use function printf;
use function sprintf;
use SebastianBergmann\Version;

final readonly class Application
{
    private const string VERSION = '5.4.0';

    /**
     * @param list<string> $argv
     */
    public function run(array $argv): int
    {
        $this->printVersion();

        try {
            $arguments = (new ArgumentsBuilder)->build($argv);
        } catch (Exception $e) {
            print PHP_EOL . $e->getMessage() . PHP_EOL;

            return 1;
        }

        if ($arguments->version()) {
            return 0;
        }

        print PHP_EOL;

        if ($arguments->help()) {
            $this->help();

            return 0;
        }

        if (!$arguments->hasConfigurationFile()) {
            $this->help();

            return 1;
        }

        try {
            $summary = $this->generate($arguments);
        } catch (Exception $e) {
            print $e->getMessage() . PHP_EOL;

            return 1;
        }

        print $summary . PHP_EOL;

        return 0;
    }

    /**
     * @return non-empty-string Summary of the releases that were processed
     */
    public function generate(Arguments $arguments): string
    {
        $configuration = (new ConfigurationLoader)->load(
            $arguments->configurationFile(),
        );

        $this->createDirectory($configuration->directory());

        $hashCache = new HashCache;

        if ($configuration->hasCacheFile()) {
            $hashCache = HashCache::fromFile($configuration->cacheFile());
        }

        $releases = (new ReleaseCollector($hashCache))->collect($configuration->directory());

        if ($configuration->hasCacheFile()) {
            $hashCache->save($configuration->cacheFile());
        }

        $renderer = new FeedRenderer(
            $configuration->directory() . DIRECTORY_SEPARATOR . 'releases.rss',
            $configuration->domain(),
            $configuration->email(),
        );

        $renderer->render($releases);

        $renderer = new MetaDataRenderer(
            $this->directory($configuration->directory() . DIRECTORY_SEPARATOR . 'latest-version-of') . DIRECTORY_SEPARATOR,
            $configuration->domain(),
            $configuration->email(),
        );

        $renderer->render($releases);

        $renderer = new PageRenderer(
            $configuration->directory() . DIRECTORY_SEPARATOR . 'index.html',
            $configuration->domain(),
            $configuration->email(),
        );

        $renderer->render($releases);

        $renderer = new PharIoRenderer(
            $configuration->directory() . DIRECTORY_SEPARATOR . 'phive.xml',
            $configuration->domain(),
            $configuration->email(),
        );

        $renderer->render($releases);

        if ($configuration->shouldGenerateApacheConfigurationFile()) {
            $renderer = new ApacheConfigRenderer;

            $renderer->render(
                $releases,
                $configuration->apacheConfigurationFile(),
            );
        }

        if ($configuration->shouldGenerateNginxConfigurationFile()) {
            $renderer = new NginxConfigRenderer;

            $renderer->render(
                $releases,
                $configuration->nginxConfigurationFile(),
            );
        }

        $this->copyAssets($configuration->directory());

        $summary = $this->quantity(count($releases->allReleases()), 'release', 'releases') . ' processed';

        if ($configuration->hasCacheFile()) {
            $summary .= ', ' . $this->quantity($hashCache->hits(), 'cache hit', 'cache hits');
            $summary .= ', ' . $this->quantity($hashCache->misses(), 'cache miss', 'cache misses');
        }

        return $summary;
    }

    private function printVersion(): void
    {
        $path = dirname(__DIR__);

        assert($path !== '');

        printf(
            'phar-site-generator %s by Sebastian Bergmann.' . PHP_EOL,
            new Version(self::VERSION, $path)->asString(),
        );
    }

    private function help(): void
    {
        print <<<'EOT'
Usage:
  phar-site-generator <configuration file>

EOT;
    }

    /**
     * @return non-empty-string
     */
    private function quantity(int $count, string $singular, string $plural): string
    {
        return $count . ' ' . ($count === 1 ? $singular : $plural);
    }

    private function copyAssets(string $target): void
    {
        $dir = $this->directory($target . '/css');
        copy(__DIR__ . '/../assets/css/style.css', $dir . '/style.css');

        $dir = $this->directory($target . '/fonts');
        copy(__DIR__ . '/../assets/fonts/OpenSans.ttf', $dir . '/OpenSans.ttf');
        copy(__DIR__ . '/../assets/fonts/SourceCodePro.ttf', $dir . '/SourceCodePro.ttf');
    }

    private function directory(string $directory): string
    {
        if (!$this->createDirectory($directory)) {
            throw new RuntimeException(
                sprintf(
                    'Directory "%s" does not exist.',
                    $directory,
                ),
            );
        }

        return $directory;
    }

    private function createDirectory(string $directory): bool
    {
        return !(!is_dir($directory) && !@mkdir($directory, 0o777, true) && !is_dir($directory));
    }
}
