<?php

$maxChartItems = 400;

$csvAddress = 'paxcounter_data.csv';
$fp = fopen($csvAddress, 'r');

// get the first (header) line
/** @var array $header */
$header = fgetcsv($fp);

// get the rest of the rows
$dataArray = array();
while ($row = fgetcsv($fp)) {
    //var_dump($row); //exit;
    $arr = array();
    foreach ($header as $columnId => $columnName)
        //  var_dump("rowNumber", $rowNumber, "value", $col);
        //exit;
        $arr[$columnName] = $row[$columnId];
    $dataArray[] = $arr;


}

var_dump($dataArray);

/**
 * How to clean the data?
 *
 * clean the data - throw away night times
 *
 * We have a max of 400 values - take the whole timeperiod available and get make 400 intervalls out of it
 *
 * Summarize the data inside one intervall to mean values
 *
 * build new array
 *
 *
 */


// clean the data - throw away night times

$filteredDataArray = array();




