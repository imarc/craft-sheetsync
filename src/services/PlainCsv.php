<?php

namespace imarc\sheetsync\services;

class PlainCsv implements ISpreadsheet
{
    private $filename = null;
    private $file = null;
    private $labels = null;

    public function __construct($filename)
    {
        $this->filename = $filename;
        $this->file = fopen($filename, 'r');
        $this->skipByteOrderMark();
    }

    /**
     * These lines look for and skip over a byte order mark (<FEFF>) if it's
     * lurking within the CSV file, looking for an opportunity to crush your
     * soul.
     */
    private function skipByteOrderMark()
    {
        $byte_order_mark = fread($this->file, 3);
        if ($byte_order_mark != pack('H*', 'EFBBBF')) {
            rewind($this->file);
        }
    }

    public function countRows()
    {
        return count(file($this->filename));
    }

    public function getRow()
    {
        $row = fgetcsv($this->file);

        if (is_array($row) && count($row) <= 1) {
            return $this->getRow();
        }

        return $row;
    }

    /**
     * Fetches an an associative array using the spreadsheet header
     * label as columns, and the current row as the values.
     */
    public function getAssociativeRow()
    {
        $row = $this->getRow();
        if (is_array($row) && count($this->labels) != count($row)) {
            throw new \exception("row and labels don't match: " . print_r([$this->labels, $row], true));
        }
        return is_array($row) ? array_combine($this->labels, $row) : $row;
    }

    public function setRowLabels($labels)
    {
        $this->labels = $labels;
    }

    public function rewind()
    {
        rewind($this->file);
        $this->skipByteOrderMark();
    }
}
