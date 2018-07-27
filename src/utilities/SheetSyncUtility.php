<?php
/**
 * Sheet Sync plugin for Craft CMS 3.x
 *
 * Allows you to import spreadsheet files into Craft sections as entries.
 *
 * @link      https://www.imarc.com/
 * @copyright Copyright (c) 2018 Kevin Hamer
 */

namespace imarc\sheetsync\utilities;

use imarc\sheetsync\Plugin;
use imarc\sheetsync\assetbundles\sheetsyncutilityutility\SheetSyncUtilityUtilityAsset;

use Craft;
use craft\base\Utility;

/**
 * Sheet Sync Utility
 *
 * Utility is the base class for classes representing Control Panel utilities.
 *
 * https://craftcms.com/docs/plugins/utilities
 *
 * @author    Kevin Hamer
 * @package   SheetSync
 * @since     1.0.0
 */
class SheetSyncUtility extends Utility
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
        return Craft::t('sheet-sync', 'Sheet Import');
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
        return 'sheetsync-sheet-sync-utility';
    }

    /**
     * Returns the path to the utility's SVG icon.
     *
     * @return string|null The path to the utility SVG icon
     */
    public static function iconPath()
    {
        return Craft::getAlias("@imarc/sheetsync/assetbundles/sheetsyncutilityutility/dist/img/SheetSyncUtility-icon.svg");
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
        Craft::$app->getView()->registerAssetBundle(SheetSyncUtilityUtilityAsset::class);

        return Craft::$app->getView()->renderTemplate(
            'sheet-sync/_components/utilities/SheetSyncUtility',
            [
                'syncs' => Plugin::getInstance()->syncService->listSyncs(),
            ]
        );
    }
}
