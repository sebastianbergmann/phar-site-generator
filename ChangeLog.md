# ChangeLog

All notable changes are documented in this file using the [Keep a CHANGELOG](http://keepachangelog.com/) principles.

## [5.0.1] - 2025-07-04

### Fixed

* Generated `.htaccess` file is missing MIME type configuration for `*.phar` and `*.phar.asc` files

## [5.0.0] - 2025-06-13

### Added

* Added support for generating redirect configuration for Apache HTTPD

### Removed

* The "additional release series" feature has been removed
* This tool is no longer supported on PHP 8.2 and PHP 8.3

## [4.0.1] - 2023-04-23

### Changed

* Releases are now sorted using "natural order" algorithm

## [4.0.0] - 2023-04-21

### Changed

* Updated CSS and JavaScript assets

### Removed

* Hovering over a release no longer displays the PHAR's contents
* This tool is no longer supported on PHP 7.2, PHP 7.3, PHP 7.4, PHP 8.0, and PHP 8.1

## [3.0.0] - 2018-08-22

### Removed

* This tool is no longer supported on PHP 5.6, PHP 7.0, and PHP 7.1

## [2.1.0] - 2017-07-14

### Added

* New redirect rule: `package-X` to latest `package-X.Y.Z`

## [2.0.4] - 2017-01-16

### Changed

* Removed usage of inline JavaScript

## [2.0.3] - 2017-01-16

### Changed

* Updated CSS and JavaScript assets

## [2.0.2] - 2016-04-04

### Changed

* Files with "nightly" in their name are now ignored

## [2.0.1] - 2016-01-21

### Added

* New redirect rule: `package-X.Y` to latest `package-X.Y.Z`

## [2.0.0] - 2016-01-20

### Added

* Implemented generator for Nginx redirect configuration

### Changed

* An XML configuration file is now used to configure the PHAR site

## [1.5.0] - 2016-01-08

### Added

* Implemented generator for [phive](https://phar.io/) metadata (`phive.xml`)

## [1.4.1] - 2015-10-23

### Changed

* Minor tweaks and fixes

## [1.4.0] - 2015-10-22

### Changed

* Use SHA256 instead of SHA1

## [1.3.0] - 2015-10-14

### Changed

* Improved the generator for "latest version" metadata

## [1.2.0] - 2015-06-03

### Added

* Implemented generator for "latest version" metadata

## [1.1.2] - 2015-01-24

### Changed

* Reverted "Use SHA256 instead of SHA1"

## [1.1.1] - 2015-01-24

### Changed

* Use SHA256 instead of SHA1

## [1.1.0] - 2015-01-24

### Changed

* Minor tweaks and fixes

## [1.0.0] - 2015-01-23

### Added

* Initial release

[5.0.1]: https://github.com/sebastianbergmann/phar-site-generator/compare/5.0.0...5.0.1
[5.0.0]: https://github.com/sebastianbergmann/phar-site-generator/compare/4.0.1...5.0.0
[4.0.1]: https://github.com/sebastianbergmann/phar-site-generator/compare/4.0.0...4.0.1
[4.0.0]: https://github.com/sebastianbergmann/phar-site-generator/compare/3.0.0...4.0.0
[3.0.0]: https://github.com/sebastianbergmann/phar-site-generator/compare/2.1.0...3.0.0
[2.1.0]: https://github.com/sebastianbergmann/phar-site-generator/compare/2.0.2...2.1.0
[2.0.4]: https://github.com/sebastianbergmann/phar-site-generator/compare/2.0.3...2.0.4
[2.0.3]: https://github.com/sebastianbergmann/phar-site-generator/compare/2.0.2...2.0.3
[2.0.2]: https://github.com/sebastianbergmann/phar-site-generator/compare/2.0.1...2.0.2
[2.0.1]: https://github.com/sebastianbergmann/phar-site-generator/compare/2.0.0...2.0.1
[2.0.0]: https://github.com/sebastianbergmann/phar-site-generator/compare/1.5.0...2.0.0
[1.5.0]: https://github.com/sebastianbergmann/phar-site-generator/compare/1.4.1...1.5.0
[1.4.1]: https://github.com/sebastianbergmann/phar-site-generator/compare/1.4.0...1.4.1
[1.4.0]: https://github.com/sebastianbergmann/phar-site-generator/compare/1.3.0...1.4.0
[1.3.0]: https://github.com/sebastianbergmann/phar-site-generator/compare/1.2.0...1.3.0
[1.2.0]: https://github.com/sebastianbergmann/phar-site-generator/compare/1.1.2...1.2.0
[1.1.2]: https://github.com/sebastianbergmann/phar-site-generator/compare/1.1.1...1.1.2
[1.1.1]: https://github.com/sebastianbergmann/phar-site-generator/compare/1.1.0...1.1.1
[1.1.0]: https://github.com/sebastianbergmann/phar-site-generator/compare/1.0.0...1.1.0
[1.0.0]: https://github.com/sebastianbergmann/phar-site-generator/compare/4d7ef1583de1ef78ad0d874477e50cac205d1a6a...1.0.0

