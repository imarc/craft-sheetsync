<?php

namespace imarc\sheetsync\services;

class PlainCsv implements ISpreadSheet
{
    private $file = null;
    private $labels = null;

    public function __construct($filename)
    {
        $this->file = fopen($filename, 'r');
    }

    public function getRow()
    {
        return fgetcsv($this->file);
    }

    /**
     * Fetches an an associative array using the spreadsheet header
     * label as columns, and the current row as the values.
     */
    public function getAssociativeRow()
    {
        $row = $this->getRow();
        return is_array($row) ? array_combine($this->labels, $row) : $row;
    }

    public function setRowLabels($labels)
    {
        $this->labels = $labels;
    }

    public function rewind()
    {
        rewind($this->file);
    }
}
