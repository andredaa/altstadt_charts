<?php

$maxChartItems = 400;
$dayStartHour = 6;
$dayEndHour = 23;

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
    $arr = array();
    foreach ($header as $columnId => $columnName) {
        $arr[$columnName] = $row[$columnId];
    }
    $dataArray[] = $arr;
}

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
 */

// clean the data - throw away night times
$dataArrayDayTimes = array();
foreach ($dataArray as $timeValuePair) {
    /** @var DateTime $valuePairTimeInside */
    $valuePairTimeInside = new DateTime($timeValuePair[$timeColumnName]);
    $unixTimeStamp = $valuePairTimeInside->getTimestamp();
    $hour = date('H ', $unixTimeStamp);

    if ($hour < $dayEndHour && $hour >= $dayStartHour) {
        array_push($dataArrayDayTimes, $timeValuePair);
    }
}
var_dump(sizeof($dataArrayDayTimes), sizeof($dataArray));// exit;
//var_dump($dataArrayDayTimes); exit;

// get intervall length for timeframe and max chart items
$earliest = new DateTime($dataArrayDayTimes[0][$timeColumnName]);
$latest = new DateTime($dataArrayDayTimes[sizeof($dataArrayDayTimes)][$timeColumnName]);
$earliestTimeStamp = $earliest->getTimestamp();
$latestTimeStamp = $latest->getTimestamp();
// normal timestamp difference is in seconds
$intervalTimeStamp = ($latestTimeStamp - $earliestTimeStamp) / $maxChartItems;
$intervalHours = $intervalTimeStamp / 60 / 60;

//echo date('Y-m-d H:i:s', $earliestTimeStamp);
//echo date('Y-m-d H:i:s', $latestTimeStamp);
//var_dump($intervallHours);

//  Summarize the data inside one intervall to mean values
$startTimeStamp = $earliestTimeStamp;
$endTimeStamp = $startTimeStamp + $intervalTimeStamp;
$intervalValues = array();
$filteredValues = array();

foreach ($dataArrayDayTimes as $timeValuePair) {
    /** @var DateTime $time */
    $valuePairTime = new DateTime($timeValuePair[$timeColumnName]);
    $valuePairTimeStamp = $valuePairTime->getTimestamp();

    // getting only timeValuePairs from inside the interval
    if ($valuePairTimeStamp >= $startTimeStamp && $valuePairTimeStamp < $endTimeStamp) {
        array_push($intervalValues, intval($timeValuePair[$valueColumnName]));
    }

    // if we arrived at the end of the interval, compute average value
    if ($valuePairTimeStamp >= $endTimeStamp) {
        $averageValue = round(array_sum($intervalValues) / count($intervalValues));
        // save the average value for interval start time in the usual format
        $arrayFormat = array();
        $arrayFormat[$timeColumnName] = date('Y-m-d H:i:s', $startTimeStamp);
        $arrayFormat[$valueColumnName] = $averageValue;

        // add summarized value to filtered values
        array_push($filteredValues, $arrayFormat);

        // reset for next loop and jump to next interval
        $startTimeStamp = $endTimeStamp;
        $proposedEndTimeStamp = $startTimeStamp + $intervalTimeStamp;

        // jump to next day if the new interval end time would be after the $dayEndHour
        if (!(date('H', $proposedEndTimeStamp) >= $dayEndHour)) {
            $endTimeStamp = $proposedEndTimeStamp;
        } else {
            $newDay = clone $valuePairTime->add(date_interval_create_from_date_string('1 day'));
            $newDay->setTime($dayStartHour,5);
            $startTimeStamp = $newDay->getTimestamp();
            $endTimeStamp = $startTimeStamp + $intervalTimeStamp;   
            $nextDay = true;
        }
        $intervalValues = array();
    }
}

var_dump("gefilterte Werte");   
var_dump(count($filteredValues));
var_dump($filteredValues);

