<?php

namespace imarc\sheetsync\services;

use PhpOffice\PhpSpreadsheet\IOFactory;

class Spreadsheet implements ISpreadSheet
{
    private $row_iterator = null;
    private $labels = null;

    public function __construct($filename)
    {
        $file_reader = IOFactory::load($filename);
        $file_reader = IOFactory::createReaderForFile($filename);
        $file_reader->setReadDataOnly(true);
        $worksheet = $file_reader->load($filename)->getActiveSheet();
        $this->row_iterator = $worksheet->getRowIterator();
    }

    public function getRow()
    {
        if (!$this->row_iterator->valid()) {
            return null;
        }

        $row = $this->row_iterator->current();
        $this->row_iterator->next();

        $cells = [];
        foreach ($row->getCellIterator() as $cell) {
            $cells[] = $cell->getValue();
        }

        return array_map('trim', $cells);
    }

    /**
     * Fetches an an associative array using the spreadsheet header
     * label as columns, and the current row as the values.
     */
    protected function getAssociativeRow()
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
        $this->row_iterator->rewind();
    }
}

?>
