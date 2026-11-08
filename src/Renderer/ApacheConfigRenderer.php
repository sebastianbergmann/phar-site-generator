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
use function chmod;
use function error_clear_last;
use function error_get_last;
use function file_get_contents;
use function file_put_contents;
use function fileperms;
use function is_file;
use function preg_replace;
use function rename;
use function sprintf;
use function str_contains;
use function str_replace;
use function unlink;

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

    /**
     * The configuration is written to a temporary file that is then renamed,
     * so that the web server never reads a partially written file, which
     * matters when the target is an .htaccess file. The temporary file gets
     * the permissions of the file that it replaces so that the web server can
     * still read it.
     *
     * @throws RuntimeException
     */
    public function render(ReleaseCollection $releases, string $target): void
    {
        $buffer        = str_replace(self::PLACEHOLDER, $this->configuration($releases), $this->template);
        $temporaryFile = $target . '.tmp';

        error_clear_last();

        if (@file_put_contents($temporaryFile, $buffer) !== false &&
            $this->copyPermissions($target, $temporaryFile) &&
            @rename($temporaryFile, $target)) {
            return;
        }

        $error = error_get_last();

        @unlink($temporaryFile);

        throw new RuntimeException(
            sprintf(
                'Apache HTTPD configuration file "%s" could not be written: %s',
                $target,
                $error !== null ? preg_replace('/^\w+\(.*?\): /', '', $error['message']) : 'unknown error',
            ),
        );
    }

    private function copyPermissions(string $from, string $to): bool
    {
        if (!is_file($from)) {
            return true;
        }

        $permissions = @fileperms($from);

        return $permissions !== false && @chmod($to, $permissions & 0o7777);
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
