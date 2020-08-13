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
use TheSeer\fDOM\fDOMDocument;
use TheSeer\fDOM\fDOMElement;

final class ConfigurationLoader
{
    /**
     * @throws \TheSeer\fDOM\fDOMException
     */
    public function load(string $filename)
    {
        $document = new fDOMDocument;
        $document->load($filename);

        $configuration = new Configuration(
            $document->getElementsByTagName('directory')->item(0)->textContent,
            $document->getElementsByTagName('domain')->item(0)->textContent,
            $document->getElementsByTagName('email')->item(0)->textContent
        );

        if ($document->getElementsByTagName('nginx')->item(0)) {
            $configuration->setNginxConfigurationFile(
                $document->getElementsByTagName('nginx')->item(0)->textContent
            );
        }

        foreach ($document->getElementsByTagName('series') as $series) {
            assert($series instanceof fDOMElement);

            $configuration->addAdditionalReleaseSeries(
                $series->getAttribute('package'),
                $series->getAttribute('series'),
                $series->getAttribute('alias')
            );
        }

        return $configuration;
    }
}
