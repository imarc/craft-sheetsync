# CSV Sync plugin for Craft CMS 3.x

Allows you to import/sync CSV files into Craft sections as entries.

## Work in Progress

This plugin is under significant development still, although it is functional.

## Changelog

**0.10 -**

* Added 'reader' option to set the class used to read the file
* Added \imarc\sheetsync\services\PlainCsv as a secondary implementation that uses fgetcsv() for performance
