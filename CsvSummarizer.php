<?php

$maxChartItems = 400;

$csvAddress = 'paxcounter_data.csv';
$fp = fopen($csvAddress, 'r');

// get the first (header) line
/** @var array $header */
$header = fgetcsv($fp);
$timeColumnName = $header[0];
$valueColumnName = $header[1];

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

//var_dump($dataArray);

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
$dataArrayDayTimes = array();
foreach ($dataArray as $timeValuePair) {
    /** @var DateTime $time */
    $time = new DateTime($timeValuePair[$timeColumnName]);
    $unixTimeStamp = $time->getTimestamp();
    $hour = date('H ', $unixTimeStamp);

    if ($hour < 23 && $hour > 6) {
        array_push($dataArrayDayTimes, $timeValuePair);
    }
}
// var_dump($dataArrayDayTimes); exit;

// get intervall length for timeframe
$earliest = new DateTime($dataArrayDayTimes[0][$timeColumnName]);
$latest = new DateTime($dataArrayDayTimes[sizeof($dataArrayDayTimes)][$timeColumnName]);
$earliestTimeStamp = $earliest->getTimestamp();
$latestTimeStamp = $latest->getTimestamp();
// normal timestamp difference is in seconds
$intervallHours = (($latestTimeStamp - $earliestTimeStamp) / 60 /60) /$maxChartItems;

//echo date('Y-m-d H:i:s', $earliestTimeStamp);
//echo date('Y-m-d H:i:s', $latestTimeStamp);
//var_dump($intervallHours);





