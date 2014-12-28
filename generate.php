#!/usr/bin/env php
<?php
namespace SebastianBergmann\PharSiteGenerator;

require __DIR__ . '/vendor/autoload.php';

$site  = 'phar.phpunit.de';
$email = 'sebastian@phpunit.de';

$collector = new Collector;
$releases  = $collector->collect(__DIR__ . '/html');

$feedRenderer = new FeedRenderer(__DIR__ . '/html/releases.rss', $site, $email);
$feedRenderer->render($releases);

$pageRenderer = new PageRenderer(__DIR__ . '/html/index.html', $site, $email);
$pageRenderer->render($releases);
