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

abstract class AbstractRenderer
{
    private string $target;
    private string $domain;
    private string $email;

    public function __construct(string $target, string $domain, string $email)
    {
        $this->target = $target;
        $this->domain = $domain;
        $this->email  = $email;
    }

    abstract public function render(ReleaseCollection $releases);

    protected function target(): string
    {
        return $this->target;
    }

    protected function domain(): string
    {
        return $this->domain;
    }

    protected function email(): string
    {
        return $this->email;
    }
}
