<?php
/**
 * CSV Sync plugin for Craft CMS 3.x
 *
 * Allows you to import CSV files into Craft sections as entries.
 *
 * @link      https://www.imarc.com/
 * @copyright Copyright (c) 2018 Kevin Hamer
 */

namespace imarc\csvsync\console\controllers;

use imarc\csvsync\Plugin;

use Craft;
use yii\console\Controller;
use yii\helpers\Console;

/**
 * imarc/craft-csvsync
 *
 * @author    Kevin Hamer
 * @package   CsvSync
 * @since     1.0.0
 */
class DefaultController extends Controller
{
    /**
     * Set the default action to 'sync' instead of 'index'.
     */
    public $defaultAction = 'sync';

    /**
     * @var string
     *     The name of the sync to run, as configured in config/csvsync.php.
     */
    public $name = null;

    /**
     * @var string
     *     The path and filename of a CSV file to import. If not specified,
     * uses the default filename configured in config/csvsync.php for the
     * current sync.
     */
    public $file = null;

    /**
     * This is how you specify the allowable options in Craft3/Yii2.
     */
    public function options($action)
    {
        switch ($action) {
            case "sync":
                return ["name", "file"];
            default:
                return [];
        }
    }



    // Public Methods
    // =========================================================================

    /**
     * Display help for imarc/craft-csvsync plugin.
     *
     */
    public function actionHelp()
    {
        $b = "\033[1m";
        $d = "\033[0m";
        $version = Plugin::getInstance()->getVersion();

        echo "CSV Sync - $version\n\n";
        echo "    ${b}yiic csvsync --sync=NAME --file=FILENAME$d\n";
        echo "        Runs the sync NAME using the file FILENAME.\n\n";
    }

    /**
     * Runs a pre-configured sync.
     *
     */
    public function actionSync()
    {
        echo Plugin::getInstance()->syncService->sync($this->name, $this->file) . "\n";
    }
}
