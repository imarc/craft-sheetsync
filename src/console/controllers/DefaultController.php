<?php
/**
 * Sheet Sync plugin for Craft CMS 3.x
 *
 * Allows you to import spreadsheet files into Craft sections as entries.
 *
 * @link      https://www.imarc.com/
 * @copyright Copyright (c) 2018 Kevin Hamer
 */

namespace imarc\sheetsync\console\controllers;

use imarc\sheetsync\Plugin;

use Craft;
use yii\console\Controller;
use yii\helpers\Console;

/**
 * imarc/craft-sheetsync
 *
 * @author    Kevin Hamer
 * @package   SheetSync
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
     *     The name of the sync to run, as configured in config/sheetsync.php.
     */
    public $name = null;

    /**
     * @var string
     *     The path and filename of a spreadsheet to import. If not specified,
     * uses the default filename configured in config/sheetsync.php for the
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
     * Display help for imarc/craft-sheetsync plugin.
     *
     */
    public function actionHelp()
    {
        $b = "\033[1m";
        $d = "\033[0m";
        $version = Plugin::getInstance()->getVersion();

        echo "Sheet Sync - $version\n\n";
        echo "    ${b}yiic sheetsync --sync=NAME --file=FILENAME$d\n";
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
