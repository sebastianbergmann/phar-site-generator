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

use const PHP_EOL;
use function file_get_contents;
use function file_put_contents;
use function sprintf;
use function str_contains;
use function str_replace;

final readonly class ApacheConfigRenderer
{
    /**
     * Placeholder in the template that is replaced with the generated
     * MIME type and redirect configuration.
     */
    private const string PLACEHOLDER = '{configuration}';
    private string $template;

    /**
     * The template file is read when the renderer is created so that a
     * missing or broken template is reported before any file is written.
     *
     * @throws RuntimeException
     */
    public static function fromTemplateFile(string $templateFile): self
    {
        $template = @file_get_contents($templateFile);

        if ($template === false) {
            throw new RuntimeException(
                sprintf(
                    'Template file "%s" for the Apache HTTPD configuration could not be read',
                    $templateFile,
                ),
            );
        }

        if (!str_contains($template, self::PLACEHOLDER)) {
            throw new RuntimeException(
                sprintf(
                    'Template file "%s" for the Apache HTTPD configuration does not contain the %s placeholder',
                    $templateFile,
                    self::PLACEHOLDER,
                ),
            );
        }

        return new self($template);
    }

    public function __construct(string $template = self::PLACEHOLDER)
    {
        $this->template = $template;
    }

    public function render(ReleaseCollection $releases, string $target): void
    {
        file_put_contents(
            $target,
            str_replace(self::PLACEHOLDER, $this->configuration($releases), $this->template),
        );
    }

    private function configuration(ReleaseCollection $releases): string
    {
        $buffer = <<<'EOT'
AddType application/octet-stream .phar
AddType application/pgp-signature .phar.asc


EOT;

        foreach ($releases->latestReleases() as $release) {
            $buffer .= $this->redirects($release->package(), $release);
        }

        foreach ($releases->latestReleasesPerPackageAndMajorVersion() as $release) {
            $buffer .= $this->redirects($release->package() . '-' . $release->majorVersion(), $release);
        }

        foreach ($releases->latestReleasesPerPackageAndMinorVersion() as $release) {
            $buffer .= $this->redirects($release->package() . '-' . $release->minorVersion(), $release);
        }

        return $buffer;
    }

    private function redirects(string $alias, Release $release): string
    {
        $buffer = '';

        foreach ($this->suffixes($release) as $suffix) {
            $buffer .= sprintf(
                'Redirect "/%s%s" "/%s%s"' . PHP_EOL,
                $alias,
                $suffix,
                $release->asString(),
                $suffix,
            );
        }

        return $buffer;
    }

    /**
     * @return non-empty-list<non-empty-string>
     */
    private function suffixes(Release $release): array
    {
        if ($release->hasSbom()) {
            return ['.phar', '.phar.asc', '.phar.cdx.xml', '.phar.cdx.xml.asc'];
        }

        return ['.phar', '.phar.asc'];
    }
}
