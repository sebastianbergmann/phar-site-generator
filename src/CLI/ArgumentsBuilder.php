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

use SebastianBergmann\CliParser\Exception as CliParserException;
use SebastianBergmann\CliParser\Parser as CliParser;

final class ArgumentsBuilder
{
    /**
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

        $configuration = null;
        $help          = false;
        $version       = false;

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

        if (!empty($options[1])) {
            $configuration = $options[1][0];
        }

        if (!$configuration && !$help && !$version) {
            throw new ArgumentsBuilderException(
                'No configuration specified',
            );
        }

        return new Arguments(
            $configuration,
            $help,
            $version,
        );
    }
}
