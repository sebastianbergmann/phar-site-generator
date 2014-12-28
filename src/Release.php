<?php
namespace SebastianBergmann\PharSiteGenerator;

class Release
{
    /**
     * @var string
     */
    private $package;

    /**
     * @var string
     */
    private $version;

    /**
     * @var string[]
     */
    private $manifest;

    /**
     * @var string
     */
    private $date;

    /**
     * @var string
     */
    private $size;

    /**
     * @var string
     */
    private $sha1;

    /**
     * @param string  $package
     * @param string  $version
     * @param array   $manifest
     * @param string  $date
     * @param string  $size
     * @param string  $sha1
     */
    public function __construct($package, $version, array $manifest, $date, $size, $sha1)
    {
        $this->package  = $package;
        $this->version  = $version;
        $this->manifest = $manifest;
        $this->date     = $date;
        $this->size     = $size;
        $this->sha1     = $sha1;
    }

    /**
     * @return string
     */
    public function package()
    {
        return $this->package;
    }

    /**
     * @return string
     */
    public function version()
    {
        return $this->version;
    }

    /**
     * @return string[]
     */
    public function manifest()
    {
        return $this->manifest;
    }

    /**
     * @return string
     */
    public function date()
    {
        return $this->date;
    }

    /**
     * @return string
     */
    public function size()
    {
        return $this->size;
    }

    /**
     * @return string
     */
    public function sha1()
    {
        return $this->sha1;
    }
}
