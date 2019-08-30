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
    const ENTRY_ATTRIBUTES = [
        'authorId',
        'dateCreated',
        'dateUpdated',
        'enabled',
        'enabledForSite',
        'expiryDate',
        'id',
        'postDate',
        'revisionNotes',
        'slug',
        'title',
    ];

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
    protected $reader = null;

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
     * Creates a new entry.
     */
    protected function createEntry($attrs, $siteId=null)
    {
        $entry = new Entry();
        $entry->authorId = $this->config('authorId');
        $entry->sectionId = $this->section->id;
        $entry->typeId = $this->entry_type_id;
        $entry->enabled = true;

        if ($siteId || isset($attrs['siteId'])) {
            $entry->enabledForSite = true;
            $entry->siteId = $siteId ?: $attrs['siteId'];
            unset($attrs['siteId']);
        }

        foreach (static::ENTRY_ATTRIBUTES as $attr) {
            if (isset($attrs[$attr])) {
                $entry->{$attr} = $attrs[$attr];
                unset($attrs[$attr]);
            }
        }

        $entry->setFieldValues($attrs);
        $entry->slug = $this->createSlug($entry);

        Craft::$app->elements->saveElement($entry);

        return $entry;
    }

    protected function updateEntry($entry, $attrs, $siteId=null)
    {
        if ($siteId || isset($attrs['siteId'])) {
            $entry->enabledForSite = true;
            $entry->siteId = $siteId ?: $attrs['siteId'];
            unset($attrs['siteId']);
        }

        foreach (static::ENTRY_ATTRIBUTES as $attr) {
            if (isset($attrs[$attr])) {
                $entry->{$attr} = $attrs[$attr];
                unset($attrs[$attr]);
            }
        }

        $entry->setFieldValues($attrs);
        $entry->slug = $this->createSlug($entry);

        try {
            Craft::$app->elements->saveElement($entry);
        } catch (\Exception $e) {
            Craft::error(print_r($attrs, true), 'sheet-sync');
            Plugin::error("Error with row");
            throw $e;
        }

        return $entry;
    }

    protected function findExisting($row, $attrs, $siteId=null)
    {
        $query = Entry::find()
            ->section($this->section->handle)
            ->anyStatus()
            ->siteId($siteId);

        $query = $this->config('find')($query, $row);

        if ($query->count()) {
            return $query->one();
        }

        return null;
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
            $slug = filter_var($entry->{$this->config('slug')}, FILTER_DEFAULT, FILTER_FLAG_STRIP_HIGH);
            return ElementHelper::createSlug($slug);
        }
    }

    /**
     * Main method. Called by the console command, this runs the sync from the
     * spreadsheet to the CMS section.
     */
    public function sync($sync_name, $filename = null, $queue = null)
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

        $reader_class = $this->config('reader');
        $this->reader = new $reader_class($filename);

        if ($this->config('headers')) {
            $headers = $this->config('headers');
            if (is_callable($headers)) {
                $headers = $headers($this->reader);
            }
            $this->reader->setRowLabels($headers);
        } else {
            $this->reader->setRowLabels($this->reader->getRow());
        }

        $total_imported = 0;
        $used_keys = ['and'];
        $num_rows = $this->reader->countRows();
        Plugin::info("Processing $num_rows rows.");

        while ($row = $this->reader->getAssociativeRow()) {

            Plugin::debug("Row: " . json_encode($row));

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

            foreach ($this->config('fields') as $field => $definition) {
                if (is_callable($definition)) {
                    $attrs[$field] = $definition($row, $this);
                }
            }

            $attrs = array_map(function($val) {
                if (is_string($val)) {
                    return \ForceUTF8\Encoding::toUTF8($val);
                } else {
                    return $val;
                }
            }, $attrs);

            Plugin::debug("Attrs: " . json_encode($attrs));

            if (isset($attrs['siteId']) && is_array($attrs['siteId'])) {

                $entry = null;
                $lastEntry = null;

                if (count($attrs['siteId']) == 0) {
                    Plugin::warning("No enabled sites for row: " . json_encode($attrs));
                }

                foreach ($attrs['siteId'] as $siteId) {

                    if ($lastEntry) {
                        $entry = Entry::find()->id($lastEntry->id)->siteId($siteId)->anyStatus()->one();
                    } else {
                        $entry = $this->findExisting($row, $attrs, $siteId);
                    }

                    if ($entry) {
                        $this->updateEntry($entry, $attrs, $siteId);
                        Plugin::info("Updated Entry: " . $entry->id);

                    } else {
                        // If we haven't created any Entry at all yet, create one
                        $entry = $lastEntry = $this->createEntry($attrs, $siteId);
                        Plugin::info("Created Entry: " . $lastEntry->id);
                    }
                }
            } else {
                $entry = $this->findExisting($row, $attrs);

                if ($entry) {
                    $this->updateEntry($entry, $attrs);
                    Plugin::info("Updated Entry: " . $entry->id);
                } else {
                    $entry = $this->createEntry($attrs);
                    Plugin::info("Created Entry: " . $entry->id);
                }
            }

            if (!$entry) {
                continue;
            }

            if ($this->config('cleanUpOnKey')) {
                $used_keys[] = 'not ' . $entry->{$this->config('cleanUpOnKey')};
            }

            $total_imported++;
            if ($queue) {
                $queue->setProgress(round(100 * $total_imported / $num_rows));
            }
        }

        if ($this->config('cleanUpOnKey') && count($used_keys) && $total_imported > $this->config('minImport')) {
            $query = Entry::find()
                ->section($this->section->handle)
                ->typeId($this->entry_type_id)
                ->{$this->config('cleanUpOnKey')}(':notempty:')
                ->{$this->config('cleanUpOnKey')}($used_keys)
                ->limit(null);

            foreach ($query->all() as $entry) {
                Craft::$app->elements->deleteElement($entry);
            }

        }


        return "success";
    }
}
