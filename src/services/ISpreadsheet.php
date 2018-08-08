<?php

namespace imarc\sheetsync\services;

interface ISpreadSheet
{
    public function __construct($filename);
    public function getRow();
    public function getAssociativeRow();
    public function rewind();
    public function setRowLabels($labels);
}
