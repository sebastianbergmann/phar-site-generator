<?php
/*
 * This file is part of phar-site-generator.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SebastianBergmann\PharSiteGenerator;

class Configuration
{
    /**
     * @var string
     */
    private $directory;

    /**
     * @var string
     */
    private $domain;

    /**
     * @var string
     */
    private $email;

    /**
     * @var string
     */
    private $nginxConfigurationFile;

    /**
     * @param string $directory
     * @param string $domain
     * @param string $email
     */
    public function __construct($directory, $domain, $email)
    {
        $this->directory = $directory;
        $this->domain    = $domain;
        $this->email     = $email;
    }

    /**
     * @param string $filename
     */
    public function setNginxConfigurationFile($filename)
    {
        $this->nginxConfigurationFile = $filename;
    }

    /**
     * @return string
     */
    public function directory()
    {
        return $this->directory;
    }

    /**
     * @return string
     */
    public function domain()
    {
        return $this->domain;
    }

    /**
     * @return string
     */
    public function email()
    {
        return $this->email;
    }

    /**
     * @return bool
     */
    public function shouldGenerateNginxConfigurationFile()
    {
        return $this->nginxConfigurationFile !== null;
    }

    /**
     * @return string
     */
    public function nginxConfigurationFile()
    {
        return $this->nginxConfigurationFile;
    }
}
