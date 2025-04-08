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

use function assert;
use function file_get_contents;
use DOMDocument;

final readonly class ConfigurationLoader
{
    public function load(string $filename): Configuration
    {
        $buffer = file_get_contents($filename);

        assert($buffer !== false);

        $document = new DOMDocument;
        $document->loadXML($buffer);

        $apacheConfigurationFile = null;

        if ($document->getElementsByTagName('apache')->item(0) !== null) {
            $apacheConfigurationFile = $document->getElementsByTagName('apache')->item(0)->textContent;
        }

        $nginxConfigurationFile = null;

        if ($document->getElementsByTagName('nginx')->item(0) !== null) {
            $nginxConfigurationFile = $document->getElementsByTagName('nginx')->item(0)->textContent;
        }

        $directory = $document->getElementsByTagName('directory')->item(0);
        $domain    = $document->getElementsByTagName('domain')->item(0);
        $email     = $document->getElementsByTagName('email')->item(0);

        assert($directory !== null);
        assert($domain !== null);
        assert($email !== null);

        return new Configuration(
            $directory->textContent,
            $domain->textContent,
            $email->textContent,
            $apacheConfigurationFile,
            $nginxConfigurationFile,
        );
    }
}
