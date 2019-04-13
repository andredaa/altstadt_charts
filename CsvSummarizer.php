<?php

$csvAddress = 'paxcounter_data.csv';

//$csvFile = fopen("filename.csv", "r");

//$data = fgetcsv($csvFile, 0, ",");

$fp = fopen($csvAddress, 'r');

// get the first (header) line
/** @var array $header */
$header = fgetcsv($fp);
$timeColumnName = $header[0];
$valueColumnName = $header[1];

var_dump($timeColumnName, $valueColumnName); //exit;

// get the rest of the rows
$data = array();
while ($row = fgetcsv($fp)) {
    $arr = array();
    foreach ($header as $i => $col)
        var_dump($i, $col);
        exit;
        $arr[$col] = $row[$i];
    $data[] = $arr;
}

//print_r($data);