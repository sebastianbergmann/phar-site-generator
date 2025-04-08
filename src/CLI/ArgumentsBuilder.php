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

use function is_string;
use function trim;
use SebastianBergmann\CliParser\Exception as CliParserException;
use SebastianBergmann\CliParser\Parser as CliParser;

final readonly class ArgumentsBuilder
{
    /**
     * @param list<string> $argv
     *
     * @throws ArgumentsBuilderException
     */
    public function build(array $argv): Arguments
    {
        try {
            $options = (new CliParser)->parse(
                $argv,
                'hv',
                [
                    'help',
                    'version',
                ],
            );
        } catch (CliParserException $e) {
            throw new ArgumentsBuilderException(
                $e->getMessage(),
                (int) $e->getCode(),
                $e,
            );
        }

        $configurationFile = null;
        $help              = false;
        $version           = false;

        foreach ($options[0] as $option) {
            switch ($option[0]) {
                case 'h':
                case '--help':
                    $help = true;

                    break;

                case 'v':
                case '--version':
                    $version = true;

                    break;
            }
        }

        if (isset($options[1][0]) && is_string($options[1][0]) && trim($options[1][0]) !== '') {
            $configurationFile = trim($options[1][0]);
        }

        if ($configurationFile === null && !$help && !$version) {
            throw new ArgumentsBuilderException(
                'No configurationFile specified',
            );
        }

        return new Arguments(
            $configurationFile,
            $help,
            $version,
        );
    }
}
