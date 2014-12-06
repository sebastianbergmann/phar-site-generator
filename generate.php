#!/usr/bin/env php
<?php
namespace SebastianBergmann\PharSite;

require __DIR__ . '/vendor/autoload.php';

$site  = 'phar.phpunit.de';
$email = 'sebastian@phpunit.de';

$collector = new Collector;

$releases = $collector->collect(__DIR__ . '/html');

$page = new \Text_Template(__DIR__ . '/templates/page.html');
$feed = new \Text_Template(__DIR__ . '/templates/feed.xml');
$item = new \Text_Template(__DIR__ . '/templates/item.xml');

$htmlLatest = '';
$htmlOld    = '';
$rdfList    = '';
$rdfItems   = '';

foreach ($releases as $package => $versions) {
    foreach ($versions as $version => $release) {
        /** @var Release $release */
        if ($release->isLatest()) {
            $htmlLatest .= (string) $release;

            $rdfList .= sprintf(
                '    <rdf:li rdf:resource="%s/%s-%s.phar"/>',
                $site,
                $package,
                $version
            );

            $item->setVar(
                array(
                    'site'    => $site,
                    'package' => $package,
                    'version' => $version,
                    'date'    => $release->date(),
                    'content' => ''
                )
            );

            $rdfItems .= $item->render();
        } else {
            $htmlOld .= (string) $release;
        }
    }
}

$page->setVar(
    array(
        'latest_releases' => $htmlLatest,
        'old_releases'    => $htmlOld,
        'site'            => $site
    )
);

$page->renderTo(__DIR__ . '/html/index.html');

$feed->setVar(
    array(
        'items_list' => $rdfList,
        'items'      => $rdfItems,
        'site'       => $site,
        'email'      => $email
    )
);

$feed->renderTo(__DIR__ . '/html/releases.rss');
