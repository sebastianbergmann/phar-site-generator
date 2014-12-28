<?php
namespace SebastianBergmann\PharSiteGenerator;

abstract class AbstractRenderer
{
    /**
     * @var string
     */
    private $target;

    /**
     * @var string
     */
    private $site;

    /**
     * @var string
     */
    private $email;

    /**
     * @param string $target
     * @param string $site
     * @param string $email
     */
    public function __construct($target, $site, $email)
    {
        $this->target = $target;
        $this->site   = $site;
        $this->email  = $email;
    }

    /**
     * @return string
     */
    protected function target()
    {
        return $this->target;
    }

    /**
     * @return string
     */
    protected function site()
    {
        return $this->site;
    }

    /**
     * @return string
     */
    protected function email()
    {
        return $this->email;
    }

    /**
     * @param ReleaseCollection $releases
     */
    abstract public function render(ReleaseCollection $releases);
}
