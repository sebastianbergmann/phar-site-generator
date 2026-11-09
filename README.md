[![CI Status](https://github.com/sebastianbergmann/phar-site-generator/workflows/CI/badge.svg)](https://github.com/sebastianbergmann/phar-site-generator/actions)
[![codecov](https://codecov.io/gh/sebastianbergmann/phar-site-generator/branch/main/graph/badge.svg)](https://codecov.io/gh/sebastianbergmann/phar-site-generator)

# phar-site-generator

`phar-site-generator` is a tool that generates HTML pages ([example](https://phar.phpunit.de/)), RSS feed ([example](https://phar.phpunit.de/releases.rss)), and Phive metadata ([example](https://phar.phpunit.de/phive.xml)) for a PHAR repository.

This tool makes the following assumptions:

* The PHAR repository is hosted using [Apache HTTPD](https://httpd.apache.org/) or [nginx](http://nginx.org/)
* The PHAR repository is hosted using HTTPS
* The PHAR repository directory contains `.phar` (PHP Archive) and `.phar.asc` (GPG signature) files
* A `.phar` file may be accompanied by a `.phar.cdx.xml` file (Software Bill of Materials in [CycloneDX](https://cyclonedx.org/) format) and its `.phar.cdx.xml.asc` (GPG signature) file
* A `.phar` file may contain a `manifest.txt` (or `phar/manifest.txt`) file that lists the package of the PHAR on its first line and the dependencies bundled in the PHAR on the following lines, one `vendor/package: version` per line, as printed by the `--manifest` option of the PHARs of PHPUnit, for instance

The generated `index.html` page lists the latest release of each PHAR, most recent first, and links to the generated `all-releases.html` page, which lists all releases.

The dependencies bundled in a PHAR are listed in the generated HTML pages, collapsed by default. The `manifest.txt` file is read from the PHAR without executing the PHAR.

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

The optional `template` attribute of the `<apache>` element configures a template file for the Apache HTTPD configuration. Use it when the file must contain configuration in addition to the generated configuration, which is often the case for an `.htaccess` file. The configured file is written with the content of the template file, in which the `{configuration}` placeholder is replaced with the generated configuration:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phar-site>
    <domain>phar.phpunit.de</domain>
    <email>sebastian@phpunit.de</email>
    <directory>/webspace/phar.phpunit.de/html</directory>
    <apache template="/webspace/phar.phpunit.de/htaccess.template">/webspace/phar.phpunit.de/html/.htaccess</apache>
</phar-site>
```

When the template file cannot be read or does not contain the `{configuration}` placeholder, `phar-site-generator` reports an error and exits with exit code `1` before any file is written.

The Apache HTTPD configuration is first written to a temporary file next to the configured file, with the suffix `.tmp`, that then replaces the configured file. This way, Apache HTTPD never reads a partially written `.htaccess` file. The configured file keeps its permissions. When the file cannot be written, `phar-site-generator` reports an error and exits with exit code `1`.

Both the `<apache>` and the `<nginx>` element are optional and can be used together.

A leading `~` in any of the configured paths is expanded to the home directory of the user that runs `phar-site-generator`.

The optional `<cache>` element configures a file in which information about the `.phar` files, their SHA-256 hash and the dependencies bundled in them, is cached between runs:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phar-site>
    <domain>phar.phpunit.de</domain>
    <email>sebastian@phpunit.de</email>
    <directory>/webspace/phar.phpunit.de/html</directory>
    <nginx>/webspace/phpunit.de/phar/redirects.conf</nginx>
    <cache>/webspace/phpunit.de/phar/cache.json</cache>
</phar-site>
```

Without a cache, every `.phar` file is read and hashed on every run. With a cache, only `.phar` files that were not seen before, or whose size or modification time changed, are read and hashed. The cache file should not be located in the PHAR repository directory and should not be shared between configurations. When the cache file cannot be written, `phar-site-generator` reports an error and exits with exit code `1`.

The optional `<latest-releases>` element configures packages, by name, that are not listed in the "Latest Releases" section of the generated `index.html` page:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phar-site>
    <domain>phar.phpunit.de</domain>
    <email>sebastian@phpunit.de</email>
    <directory>/webspace/phar.phpunit.de/html</directory>
    <nginx>/webspace/phpunit.de/phar/redirects.conf</nginx>
    <latest-releases>
        <ignore>foal</ignore>
        <ignore>phpcpd</ignore>
        <ignore>phploc</ignore>
        <ignore>phpunit-library</ignore>
    </latest-releases>
</phar-site>
```

The releases of an ignored package are still listed on the generated `all-releases.html` page, and they are still included in the RSS feed, the Phive metadata, and the redirect configuration.
