#!/usr/bin/env php
<?php declare(strict_types=1);

if (!isset($argv[1], $argv[2])) {
    exit(1);
}

$file   = __DIR__ . '/../tmp/phar/src/CLI/Application.php';
$buffer = \preg_replace(
    '/private const string VERSION = \'[^\']+\';/',
    'private const string VERSION = \'' . $argv[1] . '\';',
    \file_get_contents($file),
    -1,
    $count
);

if ($count !== 1) {
    \fwrite(\STDERR, 'Unable to set the version in ' . $file . \PHP_EOL);

    exit(1);
}

\file_put_contents($file, $buffer, \LOCK_EX);

if ($argv[2] === 'release') {
    print $argv[1];
} else {
    print $argv[2];
}
