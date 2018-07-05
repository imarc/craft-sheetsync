<?php

namespace imarc\csvsync\models;

use craft\base\Model;

class Settings extends Model
{
    public $authorId  = 1;
    public $delimiter = ',';
    public $enclosure = '"';
    public $escape    = '\\';
    public $syncs     = [];
}
