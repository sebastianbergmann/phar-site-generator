#!/usr/bin/env php
<!DOCTYPE html>
<html lang="en">
 <head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="css/bootstrap.min.css" type="text/css" rel="stylesheet">
  <link href="css/style.css" type="text/css" rel="stylesheet">
  <!--[if lt IE 9]><script src="js/html5shiv.js"></script><![endif]-->
  <link href="https://fonts.googleapis.com/css?family=Open+Sans:400,700" rel="stylesheet" type="text/css">
  <title>phar.phpunit.de</title>
 </head>
 <body>
  <div class="container">
   <h1>phar.phpunit.de</h1>
   <h2>Latest Releases</h2>
   <div class="table-responsive">
    <table class="table table-hover">
     <thead>
      <tr>
       <th>PHAR</th>
       <th>Size</th>
       <th>Last Modified</th>
       <th>GPG Signature</th>
       <th>SHA1 Checksum</th>
      </tr>
     </thead>
     <tbody>
<?php
function human_filesize($bytes) {
  $sz = 'BKMGTP';
  $factor = floor((strlen($bytes) - 1) / 3);
  return sprintf("%.2f", $bytes / pow(1024, $factor)) . @$sz[$factor];
}

$packages = array();

foreach (new GlobIterator(__DIR__ . '/html/*.phar') as $file) {
    if (!$file->isLink() &&
        stripos($file->getBasename(), 'alpha') === false &&
        stripos($file->getBasename(), 'beta') === false) {
        $parts    = explode('-', $file->getBasename('.phar'));
        $version  = array_pop($parts);
        $name     = join('-', $parts);
        $manifest = '';

        if (strpos(file_get_contents($file->getPathname()), '--manifest')) {
            $output = array();
            @exec($file->getPathname() . ' --manifest 2> /dev/null', $output);

            $manifest = sprintf(
                ' class="phar" data-title="Manifest" data-content="<ul>%s</ul>" data-placement="bottom" data-html="true"',
                join('', array_map(function ($item) { return '<li>' . $item . '</li>'; }, $output))
            );
        }

        if (!isset($packages[$name])) {
            $packages[$name] = array();
        }

        $packages[$name][$version] = array(
            'manifest' => $manifest,
            'mtime'    => date(DATE_W3C, $file->getMTime()),
            'size'     => human_filesize($file->getSize()),
            'sha1'     => sha1_file($file->getPathname()),
            'gpg'      => file_exists($file->getPathname() . '.asc') ? sprintf('<a href="https://phar.phpunit.de/%s.asc">%s.asc</a>', $file->getBasename(), $file->getBasename()) : ''
        );
    }
}

ksort($packages);

foreach ($packages as $package => $releases) {
    uksort($releases, 'strnatcmp');

    $releases = array_reverse($releases, true);

    foreach ($releases as $release => $data) {
        print_release($package, $release, $data, true);
        break;
    }
}
?>
     </tbody>
    </table>
   </div>
   <hr/>
   <h2>Old Releases</h2>
   <div class="table-responsive">
    <table class="table table-hover">
     <thead>
      <tr>
       <th>PHAR</th>
       <th>Size</th>
       <th>Last Modified</th>
       <th>GPG Signature</th>
       <th>SHA1 Checksum</th>
      </tr>
     </thead>
     <tbody>
<?php
foreach ($packages as $package => $releases) {
    uksort($releases, 'strnatcmp');

    $releases = array_reverse($releases, true);
    $latest   = true;

    foreach ($releases as $release => $data) {
        if ($latest) {
            $latest = false;
            continue;
        }

        print_release($package, $release, $data, false);
    }
}

function print_release($package, $release, array $data, $latest)
{
        printf(
            '      <tr%s>
       <td>%s<a href="https://phar.phpunit.de/%s-%s.phar">%s-%s.phar</a>%s</td>
       <td>%s%s%s</td>
       <td>%s%s%s</td>
       <td>%s%s%s</td>
       <td>%s<tt>%s</tt>%s</td>
      </tr>
',
            $data['manifest'],
            $latest ? '<strong>' : '',
            $package,
            $release,
            $package,
            $release,
            $latest ? '</strong>' : '',
            $latest ? '<strong>' : '',
            $data['size'],
            $latest ? '</strong>' : '',
            $latest ? '<strong>' : '',
            $data['mtime'],
            $latest ? '</strong>' : '',
            $latest ? '<strong>' : '',
            $data['gpg'],
            $latest ? '</strong>' : '',
            $latest ? '<strong>' : '',
            $data['sha1'],
            $latest ? '</strong>' : ''
        );
}
?>
     </tbody>
    </table>
   </div>
  </div>
  <script type="text/javascript" src="js/jquery.min.js"></script>
  <script type="text/javascript" src="js/bootstrap.min.js"></script>
  <script type="text/javascript">
  $(function() {
    $('.phar').popover({trigger: 'hover'});
  });

  var _paq = _paq || [];
  _paq.push(["trackPageView"]);
  _paq.push(["enableLinkTracking"]);

  (function() {
    var u=(("https:" == document.location.protocol) ? "https" : "http") + "://piwik.sebastian-bergmann.de/";
    _paq.push(["setTrackerUrl", u+"piwik.php"]);
    _paq.push(["setSiteId", "2"]);
    var d=document, g=d.createElement("script"), s=d.getElementsByTagName("script")[0]; g.type="text/javascript";
    g.defer=true; g.async=true; g.src=u+"piwik.js"; s.parentNode.insertBefore(g,s);
  })();
  </script>
 </body>
</html>

