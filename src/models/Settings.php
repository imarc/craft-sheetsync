<?php

namespace imarc\sheetsync\models;

use craft\base\Model;
use imarc\sheetsync\services\Spreadsheet;

class Settings extends Model
{
    public $authorId  = 1;
    public $delimiter = ',';
    public $enclosure = '"';
    public $escape    = '\\';
    public $syncs     = [];
    public $minImport = 0;
    public $reader    = Spreadsheet::class;
}
