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
use function dirname;
use function is_dir;
use function mkdir;
use function printf;
use function sprintf;
use SebastianBergmann\Version;

final readonly class Application
{
    private const string VERSION = '5.0';

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

        $this->generate($arguments);

        return 0;
    }

    public function generate(Arguments $arguments): void
    {
        $configuration = (new ConfigurationLoader)->load(
            $arguments->configurationFile(),
        );

        $this->createDirectory($configuration->directory());

        $releases = (new ReleaseCollector)->collect($configuration->directory());

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

    private function copyAssets(string $target): void
    {
        $dir = $this->directory($target . '/css');
        copy(__DIR__ . '/../assets/css/bootstrap.min.css', $dir . '/bootstrap.min.css');
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
