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
    private $domain;

    /**
     * @var string
     */
    private $email;

    /**
     * @param string $target
     * @param string $domain
     * @param string $email
     */
    public function __construct($target, $domain, $email)
    {
        $this->target = $target;
        $this->domain = $domain;
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
    protected function domain()
    {
        return $this->domain;
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
