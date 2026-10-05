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
use function getenv;
use function sprintf;
use function str_starts_with;
use function substr;
use DOMDocument;

final readonly class ConfigurationLoader
{
    /**
     * @throws RuntimeException
     */
    public function load(string $filename): Configuration
    {
        $buffer = file_get_contents($filename);

        if ($buffer === false || $buffer === '') {
            throw new RuntimeException(
                sprintf(
                    'Configuration file "%s" could not be read or is empty',
                    $filename,
                ),
            );
        }

        $document = new DOMDocument;
        $document->loadXML($buffer);

        $apacheConfigurationFile = null;

        if ($document->getElementsByTagName('apache')->item(0) !== null) {
            $apacheConfigurationFile = $this->expandHomeDirectory($document->getElementsByTagName('apache')->item(0)->textContent);
        }

        $nginxConfigurationFile = null;

        if ($document->getElementsByTagName('nginx')->item(0) !== null) {
            $nginxConfigurationFile = $this->expandHomeDirectory($document->getElementsByTagName('nginx')->item(0)->textContent);
        }

        $cacheFile = null;

        if ($document->getElementsByTagName('cache')->item(0) !== null) {
            $cacheFile = $this->expandHomeDirectory($document->getElementsByTagName('cache')->item(0)->textContent);
        }

        $directory = $document->getElementsByTagName('directory')->item(0);
        $domain    = $document->getElementsByTagName('domain')->item(0);
        $email     = $document->getElementsByTagName('email')->item(0);

        assert($directory !== null);
        assert($domain !== null);
        assert($email !== null);

        return new Configuration(
            $this->expandHomeDirectory($directory->textContent),
            $domain->textContent,
            $email->textContent,
            $apacheConfigurationFile,
            $nginxConfigurationFile,
            $cacheFile,
        );
    }

    /**
     * Expands a leading "~" to the home directory of the current user,
     * as a shell would do, because PHP's filesystem functions do not.
     *
     * @throws RuntimeException
     */
    private function expandHomeDirectory(string $path): string
    {
        if ($path !== '~' && !str_starts_with($path, '~/')) {
            return $path;
        }

        $home = getenv('HOME');

        if ($home === false || $home === '') {
            throw new RuntimeException(
                sprintf(
                    'Path "%s" cannot be expanded because the HOME environment variable is not set',
                    $path,
                ),
            );
        }

        return $home . substr($path, 1);
    }
}
