#!/usr/bin/env php
<?php
namespace SebastianBergmann\PharSite;

require __DIR__ . '/vendor/autoload.php';

$collector = new Collector;

$releases = $collector->collect(__DIR__ . '/html');

$page = new \Text_Template(__DIR__ . '/templates/page.html');

$latest = '';
$old    = '';

foreach ($releases as $package => $versions) {
    foreach ($versions as $version) {
        /** @var Release $version */
        if ($version->isLatest()) {
            $latest .= (string) $version;
        } else {
            $old .= (string) $version;
        }
    }
}

$page->setVar(
    array(
        'latest_releases' => $latest,
        'old_releases'    => $old,
        'site'            => 'phar.phpunit.de'
    )
);

$page->renderTo(__DIR__ . '/html/index.html');
