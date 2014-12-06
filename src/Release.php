<?php
namespace SebastianBergmann\PharSite;

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
     * @var array
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
     * @var boolean
     */
    private $isLatest = false;

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
    public function __toString()
    {
        return sprintf(
            '      <tr%s>
       <td>%s<a href="https://phar.phpunit.de/%s-%s.phar">%s-%s.phar</a>%s</td>
       <td>%s%s%s</td>
       <td>%s%s%s</td>
       <td>%s<a href="https://phar.phpunit.de/%s-%s.phar.asc">%s-%s.phar.asc</a>%s</td>
       <td>%s<tt>%s</tt>%s</td>
      </tr>
',
            sprintf(
                ' class="phar" data-title="Manifest" data-content="<ul>%s</ul>" data-placement="bottom" data-html="true"',
                join(
                    '',
                    array_map(
                        function ($item) {
                            return '<li>' . $item . '</li>';
                        },
                        $this->manifest
                    )
                )
            ),
            $this->isLatest ? '<strong>' : '',
            $this->package,
            $this->version,
            $this->package,
            $this->version,
            $this->isLatest ? '</strong>' : '',
            $this->isLatest ? '<strong>' : '',
            $this->size,
            $this->isLatest ? '</strong>' : '',
            $this->isLatest ? '<strong>' : '',
            $this->date,
            $this->isLatest ? '</strong>' : '',
            $this->isLatest ? '<strong>' : '',
            $this->package,
            $this->version,
            $this->package,
            $this->version,
            $this->isLatest ? '</strong>' : '',
            $this->isLatest ? '<strong>' : '',
            $this->sha1,
            $this->isLatest ? '</strong>' : ''
        );
    }

    public function latest()
    {
        $this->isLatest = true;
    }

    /**
     * @return boolean
     */
    public function isLatest()
    {
        return $this->isLatest;
    }

    /**
     * @return string
     */
    public function date()
    {
        return $this->date;
    }
}
