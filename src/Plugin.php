<?php
/**
 * Sheet Sync plugin for Craft CMS 3.x
 *
 * Allows you to import Sheet files into Craft sections as entries.
 *
 * @link      https://www.imarc.com/
 * @copyright Copyright (c) 2018 Kevin Hamer
 */

namespace imarc\sheetsync;

use imarc\sheetsync\services\SyncService as SyncServiceService;
use imarc\sheetsync\utilities\SheetSyncUtility as SheetSyncUtilityUtility;
use imarc\sheetsync\models\Settings;
use imarc\sheetsync\controllers\DefaultController;

use Craft;
use craft\console\Application as ConsoleApplication;
use craft\services\Utilities;
use craft\events\RegisterComponentTypesEvent;

use yii\base\Event;

/**
 * Craft plugins are very much like little applications in and of themselves. We’ve made
 * it as simple as we can, but the training wheels are off. A little prior knowledge is
 * going to be required to write a plugin.
 *
 * For the purposes of the plugin docs, we’re going to assume that you know PHP and SQL,
 * as well as some semi-advanced concepts like object-oriented programming and PHP namespaces.
 *
 * https://craftcms.com/docs/plugins/introduction
 *
 * @author    Kevin Hamer
 * @package   SheetSync
 * @since     1.0.0
 *
 * @property  SyncServiceService $syncService
 */
class Plugin extends \craft\base\Plugin
{
    // Public Properties
    // =========================================================================

    /**
     * To execute your plugin’s migrations, you’ll need to increase its schema version.
     *
     * @var string
     */
    public $schemaVersion = '1.0.0';


    public $controllerMap = [
        'sync' => DefaultController::class,
    ];

    // Public Methods
    // =========================================================================

    /**
     * Called after the plugin class is instantiated; do any one-time initialization
     * here such as hooks and events.
     *
     * If you have a '/vendor/autoload.php' file, it will be loaded for you automatically;
     * you do not need to load it in your init() method.
     *
     */
    public function init()
    {
        parent::init();

        // Add in our console commands
        if (Craft::$app instanceof ConsoleApplication) {
            $this->controllerNamespace = 'imarc\sheetsync\console\controllers';
        } else {
            $this->controllerNamespace = 'imarc\sheetsync\controllers';
        }

        // Register our utilities
        Event::on(
            Utilities::class,
            Utilities::EVENT_REGISTER_UTILITY_TYPES,
            function (RegisterComponentTypesEvent $event) {
                $event->types[] = SheetSyncUtilityUtility::class;
            }
        );

        Craft::info(
            Craft::t(
                'sheet-sync',
                '{name} plugin loaded',
                ['name' => $this->name]
            ),
            __METHOD__
        );
    }

    static public function debug(...$params)
    {
        Craft::debug(sprintf(...$params), 'sheet-sync');
    }

    static public function info(...$params)
    {
        Craft::info(sprintf(...$params), 'sheet-sync');
    }

    static public function warning(...$params)
    {
        Craft::warning(sprintf(...$params), 'sheet-sync');
    }

    static public function error(...$params)
    {
        Craft::error(sprintf(...$params), 'sheet-sync');
    }

    // Protected Methods
    // =========================================================================

    protected function createSettingsModel()
    {
        return new Settings();
    }
}
