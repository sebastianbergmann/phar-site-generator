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

use Symfony\Component\Console\Command\Command as AbstractCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class Command extends AbstractCommand
{
    protected function configure(): void
    {
        $this->setName('phar-site-generator')
             ->addArgument(
                 'configuration',
                 InputArgument::REQUIRED,
                 'The XML configuration file'
             );
    }

    /**
     * @throws \TheSeer\fDOM\fDOMException
     * @throws RuntimeException
     */
    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $configurationLoader = new ConfigurationLoader;

        $configuration = $configurationLoader->load(
            $input->getArgument('configuration')
        );

        $collector = new ReleaseCollector;
        $releases  = $collector->collect($configuration->directory());

        $renderer = new FeedRenderer(
            $configuration->directory() . \DIRECTORY_SEPARATOR . 'releases.rss',
            $configuration->domain(),
            $configuration->email()
        );

        $renderer->render($releases);

        $renderer = new MetaDataRenderer(
            $this->getDirectory($configuration->directory() . \DIRECTORY_SEPARATOR . 'latest-version-of') . \DIRECTORY_SEPARATOR,
            $configuration->domain(),
            $configuration->email()
        );

        $renderer->render($releases);

        $renderer = new PageRenderer(
            $configuration->directory() . \DIRECTORY_SEPARATOR . 'index.html',
            $configuration->domain(),
            $configuration->email()
        );

        $renderer->render($releases);

        $renderer = new PharIoRenderer(
            $configuration->directory() . \DIRECTORY_SEPARATOR . 'phive.xml',
            $configuration->domain(),
            $configuration->email()
        );

        $renderer->render($releases);

        if ($configuration->shouldGenerateNginxConfigurationFile()) {
            $renderer = new NginxConfigRenderer;

            $renderer->render(
                $releases,
                $configuration->additionalReleaseSeries(),
                $configuration->nginxConfigurationFile()
            );
        }

        $this->copyAssets($configuration->directory());
    }

    /**
     * @throws RuntimeException
     */
    private function copyAssets(string $target): void
    {
        $dir = $this->getDirectory($target . '/css');
        \copy(__DIR__ . '/../assets/css/bootstrap.min.css', $dir . '/bootstrap.min.css');
        \copy(__DIR__ . '/../assets/css/style.css', $dir . '/style.css');

        $dir = $this->getDirectory($target . '/fonts');
        \copy(__DIR__ . '/../assets/fonts/glyphicons-halflings-regular.eot', $dir . '/glyphicons-halflings-regular.eot');
        \copy(__DIR__ . '/../assets/fonts/glyphicons-halflings-regular.svg', $dir . '/glyphicons-halflings-regular.svg');
        \copy(__DIR__ . '/../assets/fonts/glyphicons-halflings-regular.ttf', $dir . '/glyphicons-halflings-regular.ttf');
        \copy(__DIR__ . '/../assets/fonts/glyphicons-halflings-regular.woff', $dir . '/glyphicons-halflings-regular.woff');
        \copy(__DIR__ . '/../assets/fonts/glyphicons-halflings-regular.woff2', $dir . '/glyphicons-halflings-regular.woff2');

        $dir = $this->getDirectory($target . '/js');
        \copy(__DIR__ . '/../assets/js/bootstrap.min.js', $dir . '/bootstrap.min.js');
        \copy(__DIR__ . '/../assets/js/html5shiv.min.js', $dir . '/html5shiv.min.js');
        \copy(__DIR__ . '/../assets/js/jquery.min.js', $dir . '/jquery.min.js');
        \copy(__DIR__ . '/../assets/js/popover.js', $dir . '/popover.js');
    }

    /**
     * @throws RuntimeException
     */
    private function getDirectory(string $directory): string
    {
        if (!$this->createDirectory($directory)) {
            throw new RuntimeException(
                \sprintf(
                    'Directory "%s" does not exist.',
                    $directory
                )
            );
        }

        return $directory;
    }

    private function createDirectory(string $directory): bool
    {
        return !(!\is_dir($directory) && !@\mkdir($directory, 0777, true) && !\is_dir($directory));
    }
}
