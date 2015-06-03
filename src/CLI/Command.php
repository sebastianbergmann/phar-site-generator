<?php
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

class Command extends AbstractCommand
{
    /**
     * Configures the current command.
     */
    protected function configure()
    {
        $this->setName('phar-site-generator')
             ->addArgument(
                 'domain',
                 InputArgument::REQUIRED,
                 'The domain the generated site is hosted at'
             )
            ->addArgument(
                'email',
                InputArgument::REQUIRED,
                'The email of the site administrator'
            )
            ->addArgument(
                'path',
                InputArgument::REQUIRED,
                'The path where the *.phar and *.phar.asc files are located and the site is generated'
            );
    }

    /**
     * Executes the current command.
     *
     * @param InputInterface  $input  An InputInterface instance
     * @param OutputInterface $output An OutputInterface instance
     *
     * @return null|integer null or 0 if everything went fine, or an error code
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $path = realpath($input->getArgument('path'));

        $collector = new Collector;
        $releases = $collector->collect($path);

        $feedRenderer = new FeedRenderer(
            $path . DIRECTORY_SEPARATOR . 'releases.rss',
            $input->getArgument('domain'),
            $input->getArgument('email')
        );

        $feedRenderer->render($releases);

        $metaDataRenderer = new MetaDataRenderer(
            $this->getDirectory($path . DIRECTORY_SEPARATOR . 'latest-version-of') . DIRECTORY_SEPARATOR,
            $input->getArgument('domain'),
            $input->getArgument('email')
        );

        $metaDataRenderer->render($releases);

        $pageRenderer = new PageRenderer(
            $path . DIRECTORY_SEPARATOR . 'index.html',
            $input->getArgument('domain'),
            $input->getArgument('email')
        );

        $pageRenderer->render($releases);

        $this->copyAssets($path);
    }

    /**
     * @param string $target
     */
    private function copyAssets($target)
    {
        $dir = $this->getDirectory($target . '/css');
        copy(__DIR__ . '/../assets/css/bootstrap.min.css', $dir . '/bootstrap.min.css');
        copy(__DIR__ . '/../assets/css/style.css', $dir . '/style.css');

        $dir = $this->getDirectory($target . '/fonts');
        copy(__DIR__ . '/../assets/fonts/glyphicons-halflings-regular.eot', $dir . '/glyphicons-halflings-regular.eot');
        copy(__DIR__ . '/../assets/fonts/glyphicons-halflings-regular.svg', $dir . '/glyphicons-halflings-regular.svg');
        copy(__DIR__ . '/../assets/fonts/glyphicons-halflings-regular.ttf', $dir . '/glyphicons-halflings-regular.ttf');
        copy(__DIR__ . '/../assets/fonts/glyphicons-halflings-regular.woff', $dir . '/glyphicons-halflings-regular.woff');
        copy(__DIR__ . '/../assets/fonts/glyphicons-halflings-regular.woff2', $dir . '/glyphicons-halflings-regular.woff2');

        $dir = $this->getDirectory($target . '/js');
        copy(__DIR__ . '/../assets/js/bootstrap.min.js', $dir . '/bootstrap.min.js');
        copy(__DIR__ . '/../assets/js/html5shiv.min.js', $dir . '/html5shiv.min.js');
        copy(__DIR__ . '/../assets/js/jquery.min.js', $dir . '/jquery.min.js');
    }

    /**
     * @param  string $directory
     * @return string
     * @throws RuntimeException
     */
    private function getDirectory($directory)
    {
        if (is_dir($directory)) {
            return $directory;
        }

        if (@mkdir($directory, 0777, true)) {
            return $directory;
        }

        throw new RuntimeException;
    }
}
