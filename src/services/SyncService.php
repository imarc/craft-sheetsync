<?php
/**
 * CSV Sync plugin for Craft CMS 3.x
 *
 * Allows you to import CSV files into Craft sections as entries.
 *
 * @link      https://www.imarc.com/
 * @copyright Copyright (c) 2018 Kevin Hamer
 */

namespace imarc\csvsync\services;

use Craft;
use craft\base\Component;
use craft\elements\Entry;
use craft\helpers\App;
use craft\helpers\ElementHelper;
use imarc\csvsync\Plugin;

/**
 * SyncService Service
 *
 * All of your plugin’s business logic should go in services, including saving data,
 * retrieving data, etc. They provide APIs that your controllers, template variables,
 * and other plugins can interact with.
 *
 * https://craftcms.com/docs/plugins/services
 *
 * @author    Kevin Hamer
 * @package   CsvSync
 * @since     1.0.0
 */
class SyncService extends Component
{
    static public function listSyncs()
    {
        $syncs = array_keys(Plugin::getInstance()->settings->syncs);
        return array_combine($syncs, $syncs);
    }

    /**
     * Fetches config values. It'll look within the configuration for the
     * specific sync first, and if it doesn't find the key it'll look within
     * the 'default' sync settings.
     */
    protected function config($key)
    {
        return $this->config[$key] ?? Plugin::getInstance()->settings[$key] ?? null;
    }



    protected $config = null;
    protected $file = null;
    protected $headers = null;

    public $section = null;
    public $entry_type_id = null;

    /**
     * Fetches a single row from the CSV via fgetcsv().
     */
    protected function getRow()
    {
        $row = fgetcsv(
            $this->file,
            0,
            $this->config('delimiter'),
            $this->config('enclosure'),
            $this->config('escape')
        );

        return is_array($row) ? array_map('trim', $row) : $row;
    }

    /**
     * Fetches an an associative array using the CSV header
     * label as columns, and the current row as the values.
     */
    protected function getAssociativeRow()
    {
        $row = $this->getRow();
        return is_array($row) ? array_combine($this->headers, $row) : $row;
    }

    /**
     * Creates a new entry.
     */
    protected function createEntry($attrs)
    {
        $entry = new Entry();
        $entry->authorId = $this->config('authorId');
        $entry->sectionId = $this->section->id;
        $entry->typeId = $this->entry_type_id;
        $entry->enabled = true;

        if (isset($attrs['title'])) {
            $entry->title = $attrs['title'];
            unset($attrs['title']);
        }
        $entry->setFieldValues($attrs);
        $entry->slug = $this->createSlug($entry);

        Craft::$app->elements->saveElement($entry);

        return $entry;
    }

    protected function updateEntry($entry, $attrs)
    {
        if (isset($attrs['title'])) {
            $entry->title = $attrs['title'];
            unset($attrs['title']);
        }
        $entry->setFieldValues($attrs);
        $entry->slug = $this->createSlug($entry);

        Craft::$app->elements->saveElement($entry);

        return $entry;
    }

    /**
     * If slug is configured as a callable, it calls it; otherwise, it fetches
     * that field from the entry and passes it through
     * ElementHelper::createSlug();
     */
    protected function createSlug($entry)
    {
        if (is_callable($this->config('slug'))) {
            return ($this->config('slug'))($entry);
        } else {
            return ElementHelper::createSlug($entry->{$this->config('slug')});
        }
    }

    /**
     * Main method. Called by the console command, this runs the sync from the
     * CSV to the CMS section.
     */
    public function sync($sync_name, $filename = null)
    {
        App::maxPowerCaptain();

        $this->config = Plugin::getInstance()->settings->syncs[$sync_name];

        if ($filename === null) {
            $filename = $this->config('filename');
            if ($filename[0] != '/') {
                $filename = Craft::$app->path->getStoragePath() . $filename;
            }
        }

        $this->section = Craft::$app->sections->getSectionByHandle($this->config('section'));

        if ($this->config('entry_type_id')) {
            $this->entry_type_id = $this->config('entry_type_id');
        } else {
            $entry_type = current($this->section->getEntryTypes());
            $this->entry_type_id = $entry_type->id;
        }

        Plugin::info("Running $sync_name with file $filename");

        $this->file = fopen($filename, "r");
        $this->headers = $this->getRow();


        if (!$this->headers) {
            return "error";
        }

        while ($row = $this->getAssociativeRow()) {

            $attrs = [];
            foreach ($this->config('fields') as $field => $definition) {
                if (!is_callable($definition)) {
                    $attrs[$field] = $row[$definition];
                }
            }

            $query = Entry::find()
                ->section($this->section->handle)
                ->typeId($this->entry_type_id);

            $entry = $this->config('find')($query, $row)->one();

            if ($entry) {
                $this->updateEntry($entry, $attrs);
            } else {
                $entry = $this->createEntry($attrs);
            }

        }

        rewind($this->file);

        // skip the first row (headers)
        $this->getRow();

        while ($row = $this->getAssociativeRow()) {
            $query = Entry::find()
                ->section($this->section->handle)
                ->typeId($this->entry_type_id);
            $entry = $this->config('find')($query, $row)->one();

            $attrs = [];
            foreach ($this->config('fields') as $field => $definition) {
                if (is_callable($definition)) {
                    $attrs[$field] = $definition($row, $this);
                }
            }

            $this->updateEntry($entry, $attrs);
        }

        return "success";
    }
}
