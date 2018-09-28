<?php
namespace imarc\sheetsync\jobs;

use Craft;
use craft\queue\BaseJob;
use imarc\sheetsync\Plugin;

class RunSync extends BaseJob
{
    public $sync;
    public $filename;

    public function getDescription()
    {
        return 'Sheet Sync';
    }

    public function execute($queue)
    {
        $status = Plugin::getInstance()->syncService->sync(
            $this->sync,
            $this->filename,
            $queue
        );

        return $status;
    }
}
