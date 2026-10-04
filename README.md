[![CI Status](https://github.com/sebastianbergmann/phar-site-generator/workflows/CI/badge.svg)](https://github.com/sebastianbergmann/phar-site-generator/actions)
[![codecov](https://codecov.io/gh/sebastianbergmann/phar-site-generator/branch/main/graph/badge.svg)](https://codecov.io/gh/sebastianbergmann/phar-site-generator)

# phar-site-generator

`phar-site-generator` is a tool that generates an HTML page ([example](https://phar.phpunit.de/)), RSS feed ([example](https://phar.phpunit.de/releases.rss)), and Phive metadata ([example](https://phar.phpunit.de/phive.xml)) for a PHAR repository.

This tool makes the following assumptions:

* The PHAR repository is hosted using [Apache HTTPD](https://httpd.apache.org/) or [nginx](http://nginx.org/)
* The PHAR repository is hosted using HTTPS
* The PHAR repository directory contains `.phar` (PHP Archive) and `.phar.asc` (GPG signature) files
* A `.phar` file may be accompanied by a `.phar.cdx.xml` file (Software Bill of Materials in [CycloneDX](https://cyclonedx.org/) format) and its `.phar.cdx.xml.asc` (GPG signature) file

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

The `<nginx>` element configures the file that the redirect configuration for nginx is written to.

When the PHAR repository is hosted using Apache HTTPD, use the `<apache>` element instead. It configures the file, for instance an `.htaccess` file in the PHAR repository directory, that the redirect configuration (as well as the MIME type configuration for `.phar` and `.phar.asc` files) for Apache HTTPD is written to:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phar-site>
    <domain>phar.phpunit.de</domain>
    <email>sebastian@phpunit.de</email>
    <directory>/webspace/phar.phpunit.de/html</directory>
    <apache>/webspace/phar.phpunit.de/html/.htaccess</apache>
</phar-site>
```

Both the `<apache>` and the `<nginx>` element are optional and can be used together.

