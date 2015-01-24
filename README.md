# phar-site-generator

`phar-site-generator` is a tool that generates an HTML page and an RSS feed for a PHAR repository. It makes the following assumptions:

* The PHAR repository directory contains `.phar` (PHP Archive) and `.phar.asc` (GPG signature) files
* The latest release of a package (`package-x.y.z.phar`, `package-x.y.z.phar.asc`) is symlinked (`package.phar`, `package.phar.asc`)
* The output of `package-x.y.z.phar --manifest`, if that option is available, is used as the manifest information for that package's release
* The PHAR repository is hosted using HTTPS

## Usage

We distribute a [PHP Archive (PHAR)](http://php.net/phar) that has all required (as well as some optional) dependencies of phar-site-generator bundled in a single file:

    wget https://phar.phpunit.de/phar-site-generator.phar

`phar-site-generator` requires three arguments:

* The domain where the PHAR repository is hosted
* The PHAR repository administrator
* The path where the `*.phar` and * *.phar.asc` files are located and the site is generated

Here is the command used to generate [phar.phpunit.de](https://phar.phpunit.de/)

    php phar-site-generator.phar phar.phpunit.de \
                                 sebastian@phpunit.de \
                                 /webspace/phar.phpunit.de/html

