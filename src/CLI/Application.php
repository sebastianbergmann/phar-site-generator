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

use SebastianBergmann\Version;
use Symfony\Component\Console\Application as AbstractApplication;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class Application extends AbstractApplication
{
    public function __construct()
    {
        $version = new Version('3.0', \dirname(__DIR__, 2));

        parent::__construct('phar-site-generator', $version->getVersion());
    }

    public function getDefinition(): InputDefinition
    {
        $inputDefinition = parent::getDefinition();
        $inputDefinition->setArguments();

        return $inputDefinition;
    }

    public function doRun(InputInterface $input, OutputInterface $output): int
    {
        $this->disableXdebug();

        if (!$input->hasParameterOption('--quiet')) {
            $output->write(
                \sprintf(
                    "phar-site-generator %s by Sebastian Bergmann.\n\n",
                    $this->getVersion()
                )
            );
        }

        if ($input->hasParameterOption('--version') ||
            $input->hasParameterOption('-V')) {
            exit;
        }

        if (!$input->getFirstArgument()) {
            $input = new ArrayInput(['--help']);
        }

        return parent::doRun($input, $output);
    }

    protected function getCommandName(InputInterface $input): string
    {
        return 'phar-site-generator';
    }

    /**
     * @throws \Symfony\Component\Console\Exception\LogicException
     */
    protected function getDefaultCommands(): array
    {
        $defaultCommands = parent::getDefaultCommands();

        $defaultCommands[] = new Command;

        return $defaultCommands;
    }

    private function disableXdebug(): void
    {
        if (!\extension_loaded('xdebug')) {
            return;
        }

        \ini_set('xdebug.scream', '0');
        \ini_set('xdebug.max_nesting_level', '8192');
        \ini_set('xdebug.show_exception_trace', '0');
        \ini_set('xdebug.show_error_trace', '0');

        \xdebug_disable();
    }
}
