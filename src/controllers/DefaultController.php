<?php
/**
 * Sheet Sync plugin for Craft CMS 3.x
 *
 * Allows you to import spreadsheet files into Craft sections as entries.
 *
 * @link      https://www.imarc.com/
 * @copyright Copyright (c) 2018 Kevin Hamer
 */

namespace imarc\sheetsync\controllers;

use Craft;
use craft\web\Controller;
use craft\web\UploadedFile;
use imarc\sheetsync\Plugin;
use imarc\sheetsync\jobs\RunSync;

/**
 * Default Controller
 *
 * Generally speaking, controllers are the middlemen between the front end of
 * the CP/website and your plugin’s services. They contain action methods which
 * handle individual tasks.
 *
 * A common pattern used throughout Craft involves a controller action gathering
 * post data, saving it on a model, passing the model off to a service, and then
 * responding to the request appropriately depending on the service method’s response.
 *
 * Action methods begin with the prefix “action”, followed by a description of what
 * the method does (for example, actionSaveIngredient()).
 *
 * https://craftcms.com/docs/plugins/controllers
 *
 * @author    Kevin Hamer
 * @package   SheetSync
 * @since     1.0.0
 */
class DefaultController extends Controller
{
    // Public Methods
    // =========================================================================

    /**
     * Handle a request going to our plugin's index action URL,
     * e.g.: actions/sheet-sync/default
     *
     * @return mixed
     */
    public function actionIndex()
    {
        $filename = null;
        $upload = UploadedFile::getInstanceByName('filename');
        if ($upload) {
            $filename = $upload->tempName;
            move_uploaded_file($filename, $filename);
        }

        Craft::$app->queue->push(new RunSync([
            'sync' => Craft::$app->request->getRequiredBodyParam('sync'),
            'filename' => $filename,
        ]));

        Craft::$app->response->redirect("?status=success");
    }
}
