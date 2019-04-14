<?php

$maxChartItems = 400;
$dayStartHour = 7;
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

// throw away night time data
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

// get intervall length for timeframe and max chart items
$earliest = new DateTime($dataArrayDayTimes[0][$timeColumnName]);
$latest = new DateTime($dataArrayDayTimes[sizeof($dataArrayDayTimes)][$timeColumnName]);
$earliestTimeStamp = $earliest->getTimestamp();
$latestTimeStamp = $latest->getTimestamp();
// normal timestamp difference is in seconds
$nightTimeSeconds = ((24-$dayEndHour) + $dayStartHour) *60 *60;
$recordingDays = floor(($latestTimeStamp - $earliestTimeStamp) / 60 / 60 / 24);
// interval is the amount of time with daytime data divided by the maxChartItems
$intervalDuration = (($latestTimeStamp - $earliestTimeStamp) - ($nightTimeSeconds * $recordingDays)) / $maxChartItems;
//$intervalDuration = 1/$intervalDuration;
//  Summarize the data inside one intervall to mean values
$startTimeStamp = $earliestTimeStamp;
$endTimeStamp = $startTimeStamp + $intervalDuration;
$intervalValues = array();
$filteredValues = array();

foreach ($dataArrayDayTimes as $timeValuePair) {
    /** @var DateTime $time */
    $valuePairTime = new DateTime($timeValuePair[$timeColumnName]);
    $valuePairTimeStamp = $valuePairTime->getTimestamp();
    // TODO es fehlen teilweise werte in der original datei
    // getting only timeValuePairs from inside the interval
    if ($valuePairTimeStamp >= $startTimeStamp && $valuePairTimeStamp < $endTimeStamp) {
        array_push($intervalValues, intval($timeValuePair[$valueColumnName]));
    }

    // there are some "holes" in the data stream, fast forward to next available datapoint
    if ($valuePairTimeStamp > $endTimeStamp && $intervalValues === []) {
        var_dump("hole in the dataset at", $timeValuePair);
        $startTimeStamp = $valuePairTimeStamp;
        $endTimeStamp = $startTimeStamp + $intervalDuration;
        continue;
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
        $proposedStartTimeStamp = $startTimeStamp + $intervalDuration;
        // jump to next day if the new interval end time would be after the $dayEndHour
        if (!(date('H', $proposedStartTimeStamp) >= $dayEndHour)) {
            $startTimeStamp = $proposedStartTimeStamp;
            $endTimeStamp = $proposedStartTimeStamp + $intervalDuration;
        } else {
            /** @var DateTime $currentDay */
            $currentDay = new DateTime(date('Y-m-d H:i:s', $startTimeStamp));
            $newDay = $currentDay->add(date_interval_create_from_date_string('1 day'));
            $newDay->setTime($dayStartHour, 0);
            $startTimeStamp = $currentDay->getTimestamp();
            $endTimeStamp = $startTimeStamp + $intervalDuration;
            $nextDay = true;
        }
        $intervalValues = array();
    }
}

var_dump("gefilterte Werte");   
var_dump(count($filteredValues));
var_dump($filteredValues[count($filteredValues)-1]);
var_dump($dataArray[count($dataArray)-1]);

// close source csv file
fclose($fp);

// open new csv file
$txtFileName = 'paxcounter_data_new.csv';// 
$fp = fopen($txtFileName, 'wb');

foreach ($filteredValues as $timeValuePair) {
    // TODO shorten the timestamp to day and month
    var_dump($timeValuePair);
    $valuePairTime = new DateTime($timeValuePair[$timeColumnName]);
    $valuePairTimeStamp = $valuePairTime->getTimestamp();
    $date = date('m-d', $valuePairTimeStamp);
    $newCsvLine = [$date, $timeValuePair[$valueColumnName]];
    fputcsv( $fp , $newCsvLine);
}
    fclose($fp);

