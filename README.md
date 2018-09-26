CSV Sync plugin for Craft CMS 3.x
=================================

Allows you to import/sync spreadsheet files into Craft sections as entries.

Work in Progress
----------------

This plugin is under significant development still, although it is functional.


Configuration
-------------

Settings should be configured in craft/config/sheet-sync.php. Default settings are grouped together, and other settings are grouped by the name of the sync:

```
return [
    'default' => [
        // default sync settings
    ],
    'name-of-a-sync' => [
        // sync specific settings
    ],
];
```

### Valid Settings

- **section** - the handle for the CMS section you'd like to sync to.
- **filename** - the name of the file you would like to sync (if not specified when the sync is called.)
- **entry_type_id** (optional) - if there's multiple entry types within the section, this will let you specify which one to use for new entries. If there's only one entry type, you don't need to specify this.
- **find** - this should be a closure (or callable) that takes two arguments, `$criteria` and `$row`, that modifies `$criteria` to pullback the corresponding entry to `$row` if it exists.
- **slug** - this is either a string (the handle for a field) or a closure (or callable.) If it's a string, then the plugin will use that field to generate a slug for the entry. If it's a closure, it's passed the current entry and expected to return a valid slug.
- **fields** - see the next section.
- **authorId** (default: 1) - ID to attribute creating entries to.
- **delimiter**, **enclosure**, and **escape** (default: ',' '"' and '\\') - these are passed directly to `fgetcsv()`.
- **reader** (optional) - Allows you to override the class used to read the file. For example, you may want to set this to `\imarc\sheetsync\services\PlainCsv` to use the significantly more performance reader that just uses fgetcsv() instead of phpoffice/phpspreadsheet.
- **headers** (optional) - Allows you override how headers for the sheet are determined. By default, the plugin will use the first row, but you can return a static array of headers, or you can write a function that takes the `$reader` as an argument and read in (and/or skip) multiple rows.
- **cleanUpOnKey** (optional) - When set, will delete any entries with this key set that aren't in the current sheet.
- **minImport** (default: 0) - Cleanup will only run if more than this number of entries were updated or created.

#### Fields

Fields are configured as an associative array. The key should be the name of a Craft field handle. The value can either be a string (and one of the CSV file column headers), or a closure (or callable) that is passed the current row (as an associative array) and the sync instance, that should return the value for this field. These closures are called **after** other populating is done, so if you need to cross reference, you can. For example, this will search through the current section for users by name based on the current row's value in 'Assistant Name' and create the relationship for Craft:

```
    // ...
    'fields' => [
        // ...
        'someUsers' => function($row, $sync) {
            $criteria = craft()->elements->getCriteria(ElementType::Entry);
            $criteria->sectionId = $sync->section->id;
            $criteria->type = $sync->entry_type_id;
            $criteria->name = $row['Assistant Name'];
            $entries = $criteria->find();

            if (count($entries)) {
                return [(int) (current($entries)->id)];

            } else {
                return null;
            }
        },
    ],
    // ...
```

Usage
-----

To run a sync, you call it via yiic:

```
$ php craft/app/etc/console/yiic  --sync=name-of-a-sync
```

You can optionally specify a specific file to use:

```
$ php craft/app/etc/console/yiic sheetsync --sync=name-of-a-sync --file=path/to/file
```
