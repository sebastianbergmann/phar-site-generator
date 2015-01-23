<?php
namespace SebastianBergmann\PharSiteGenerator\CLI;

use SebastianBergmann\PharSiteGenerator\Collector;
use SebastianBergmann\PharSiteGenerator\FeedRenderer;
use SebastianBergmann\PharSiteGenerator\PageRenderer;
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

        $pageRenderer = new PageRenderer(
            $path . DIRECTORY_SEPARATOR . 'index.html',
            $input->getArgument('domain'),
            $input->getArgument('email')
        );

        $pageRenderer->render($releases);
    }
}
