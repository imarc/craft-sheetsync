<?php
/**
 * CSV Sync plugin for Craft CMS 3.x
 *
 * Allows you to import CSV files into Craft sections as entries.
 *
 * @link      https://www.imarc.com/
 * @copyright Copyright (c) 2018 Kevin Hamer
 */

namespace imarc\csvsync\utilities;

use imarc\csvsync\Plugin;
use imarc\csvsync\assetbundles\csvsyncutilityutility\CsvSyncUtilityUtilityAsset;

use Craft;
use craft\base\Utility;

/**
 * CSV Sync Utility
 *
 * Utility is the base class for classes representing Control Panel utilities.
 *
 * https://craftcms.com/docs/plugins/utilities
 *
 * @author    Kevin Hamer
 * @package   CsvSync
 * @since     1.0.0
 */
class CsvSyncUtility extends Utility
{
    // Static
    // =========================================================================

    /**
     * Returns the display name of this utility.
     *
     * @return string The display name of this utility.
     */
    public static function displayName(): string
    {
        return Craft::t('csv-sync', 'CSV Import');
    }

    /**
     * Returns the utility’s unique identifier.
     *
     * The ID should be in `kebab-case`, as it will be visible in the URL (`admin/utilities/the-handle`).
     *
     * @return string
     */
    public static function id(): string
    {
        return 'csvsync-csv-sync-utility';
    }

    /**
     * Returns the path to the utility's SVG icon.
     *
     * @return string|null The path to the utility SVG icon
     */
    public static function iconPath()
    {
        return Craft::getAlias("@imarc/csvsync/assetbundles/csvsyncutilityutility/dist/img/CsvSyncUtility-icon.svg");
    }

    /**
     * Returns the number that should be shown in the utility’s nav item badge.
     *
     * If `0` is returned, no badge will be shown
     *
     * @return int
     */
    public static function badgeCount(): int
    {
        return 0;
    }

    /**
     * Returns the utility's content HTML.
     *
     * @return string
     */
    public static function contentHtml(): string
    {
        Craft::$app->getView()->registerAssetBundle(CsvSyncUtilityUtilityAsset::class);

        return Craft::$app->getView()->renderTemplate(
            'csv-sync/_components/utilities/CsvSyncUtility',
            [
                'syncs' => Plugin::getInstance()->syncService->listSyncs(),
            ]
        );
    }
}
