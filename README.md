# phar-site-generator

`phar-site-generator` is a tool that generates an HTML page and an RSS feed for a PHAR repository. It makes the following assumptions:

* The PHAR repository directory contains `.phar` (PHP Archive) and `.phar.asc` (GPG signature) files
* The latest release of a package (`package-x.y.z.phar`, `package-x.y.z.phar.asc`) is symlinked (`package.phar`, `package.phar.asc`)
* The output of `package-x.y.z.phar --manifest`, if that option is available, is used as the manifest information for that package's release
* The PHAR repository is hosted using HTTPS

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
</phar-site>
```

