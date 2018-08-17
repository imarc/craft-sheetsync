# CSV Sync Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/) and this project adheres to [Semantic Versioning](http://semver.org/).

## 0.11 - 2018-08-17
### Added
* new config parameter, `cleanUpOnKey`, to make the sync optionally clean up entries that aren't in the current sheet

## 0.10 - 2018-08-06
### Added
* `reader` option to set the class used to read the file
* \imarc\sheetsync\services\PlainCsv as a secondary implementation that uses fgetcsv() for performance

## 0.9 - 2018-07-27
### Added
* Support for non-CSV spreadsheets like XLSX via phpoffice/phpspreadsheet

### Changed
* Rename to sheetsync, put in GitHub

## 0.8 - 2018-07-06
### Added
- Initial release
