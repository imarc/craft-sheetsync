<?php
/**
 * Sheet Sync plugin for Craft CMS 3.x
 *
 * Allows you to import spreadsheet files into Craft sections as entries.
 *
 * @link      https://www.imarc.com/
 * @copyright Copyright (c) 2018 Kevin Hamer
 */

namespace imarc\sheetsync\services;

use Craft;
use PhpOffice\PhpSpreadsheet\IOFactory;
use craft\base\Component;
use craft\elements\Entry;
use craft\helpers\App;
use craft\helpers\ElementHelper;
use imarc\sheetsync\Plugin;
use Exception;

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
 * @package   SheetSync
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
    protected $row_iterator = null;
    protected $headers = null;

    public $section = null;
    public $entry_type_id = null;

    protected function getRow()
    {
        if (!$this->row_iterator->valid()) {
            return null;
        }

        $row = $this->row_iterator->current();
        $this->row_iterator->next();

        $cells = [];
        foreach ($row->getCellIterator() as $cell) {
            $cells[] = $cell->getValue();
        }

        return array_map('trim', $cells);
    }

    /**
     * Fetches an an associative array using the spreadsheet header
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
     * spreadsheet to the CMS section.
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

        $file_reader = IOFactory::load($filename);
        $file_reader = IOFactory::createReaderForFile($filename);
        $file_reader->setReadDataOnly(true);
        $worksheet = $file_reader->load($filename)->getActiveSheet();
        $this->row_iterator = $worksheet->getRowIterator();


        if ($this->config('headers')) {
            $this->headers = $this->config('headers')($this->row_iterator);
        } else {
            $this->headers = $this->getRow();
        }

        if (!$this->headers) {
            return "error";
        }

        while ($row = $this->getAssociativeRow()) {

            $attrs = [];
            foreach ($this->config('fields') as $field => $definition) {
                if (!is_callable($definition)) {
                    if (isset($row[$definition])) {
                        $attrs[$field] = $row[$definition];
                    } else {
                        throw new Exception("Couldn't find '$definition' in the import.");
                    }
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

        $this->row_iterator->rewind();

        // skip the first row (headers)
        if ($this->config('headers')) {
            $this->config('headers')($this->row_iterator);
        } else {
            $this->getRow();
        }

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
