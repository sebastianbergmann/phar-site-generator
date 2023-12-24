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
use DOMElement;

final class ConfigurationLoader
{
    public function load(string $filename)
    {
        $document = new DOMDocument;
        $document->loadXML(file_get_contents($filename));

        $nginxConfigurationFile = null;

        if ($document->getElementsByTagName('nginx')->item(0)) {
            $nginxConfigurationFile = $document->getElementsByTagName('nginx')->item(0)->textContent;
        }

        $configuration = new Configuration(
            $document->getElementsByTagName('directory')->item(0)->textContent,
            $document->getElementsByTagName('domain')->item(0)->textContent,
            $document->getElementsByTagName('email')->item(0)->textContent,
            $nginxConfigurationFile,
        );

        foreach ($document->getElementsByTagName('series') as $series) {
            assert($series instanceof DOMElement);

            $configuration->addAdditionalReleaseSeries(
                $series->getAttribute('package'),
                $series->getAttribute('series'),
                $series->getAttribute('alias'),
            );
        }

        return $configuration;
    }
}
