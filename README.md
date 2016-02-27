# phar-site-generator

`phar-site-generator` is a tool that generates an HTML page ([example](https://phar.phpunit.de/)), RSS feed ([example](https://phar.phpunit.de/releases.rss)), and Phive metadata ([example](https://phar.phpunit.de/phive.xml)) for a PHAR repository.

This tool makes the following assumptions:

* The PHAR repository is hosted using [nginx](http://nginx.org/)
* The PHAR repository is hosted using HTTPS
* The PHAR repository directory contains `.phar` (PHP Archive) and `.phar.asc` (GPG signature) files
* The output of `package-x.y.z.phar --manifest`, if that option is available, is used as the manifest information for that package's release

## Usage

We distribute a [PHP Archive (PHAR)](http://php.net/phar) that has all required (as well as some optional) dependencies of phar-site-generator bundled in a single file:

```
wget https://phar.phpunit.de/phar-site-generator.phar
```

`phar-site-generator` requires an XML configuration file:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phar-site>
    <domain>phar.phpunit.de</domain>
    <email>sebastian@phpunit.de</email>
    <directory>/webspace/phar.phpunit.de/html</directory>
    <nginx>/webspace/phpunit.de/phar/redirects.conf</nginx>
</phar-site>
```

