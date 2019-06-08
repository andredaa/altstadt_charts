<?php
// Set locale to German for names of months
setlocale(LC_TIME, "de_DE");

$maxChartItems = 400;
$detailedNewItemsCount = 0;
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

// throw away night time data and empty data
$dataArrayDayTimes = array();
foreach ($dataArray as $timeValuePair) {
    // filter out logs without value
    if ($timeValuePair[$valueColumnName] == '') {
        //var_dump("was empty");
        continue;
    }

    /** @var DateTime $valuePairTimeInside */
    $valuePairTimeInside = new DateTime($timeValuePair[$timeColumnName]);
    $unixTimeStamp = $valuePairTimeInside->getTimestamp();
    $month = date('m ', $unixTimeStamp);
    $day = date('d ', $unixTimeStamp);

    // remove all data before the 6th of june
    if ($month < 6 || ($month == 6 && $day < 6)) {
        continue;
    }

    $hour = date('H ', $unixTimeStamp);

    if ($hour < $dayEndHour && $hour >= $dayStartHour) {
        array_push($dataArrayDayTimes, $timeValuePair);
       // var_dump($timeValuePair);
    }
}

//var_dump(sizeof($dataArrayDayTimes), sizeof($dataArray), $dataArrayDayTimes[sizeof($dataArrayDayTimes)-1]); exit;

// get intervall length for timeframe and max chart items
// the fiveteen earliest values will be added as single vaues, without calculating averages
$earliest = new DateTime($dataArrayDayTimes[0][$timeColumnName]);
$latest = new DateTime($dataArrayDayTimes[sizeof($dataArrayDayTimes)- 1][$timeColumnName]);
$earliestTimeStamp = $earliest->getTimestamp();
$latestTimeStamp = $latest->getTimestamp();

//var_dump($earliest, $latest);

// normal timestamp difference is in seconds
$nightTimeSeconds = ((24-$dayEndHour) + $dayStartHour) *60 *60;
$recordingDays = floor(($latestTimeStamp - $earliestTimeStamp) / 60 / 60 / 24);
// interval is the amount of time with daytime data divided by the maxChartItems
$intervalDuration = (($latestTimeStamp - $earliestTimeStamp) - ($nightTimeSeconds * $recordingDays)) / $maxChartItems;
//$intervalDuration = 1/$intervalDuration;

//  Summarize the data inside one interval to mean values and add them to filtered values
$filteredValues = array();
$startTimeStamp = $earliestTimeStamp;
$endTimeStamp = $startTimeStamp + $intervalDuration;
$intervalValues = array();
$currentDay = date('d', $startTimeStamp);
$isNewDay = true;
foreach ($dataArrayDayTimes as $id => $timeValuePair) {
    /** @var DateTime $time */
    $valuePairTime = new DateTime($timeValuePair[$timeColumnName]);
    $valuePairTimeStamp = $valuePairTime->getTimestamp();

    // compute average value of intervall, once first value is outside the interval or if we arrived at and of data
    if ($valuePairTimeStamp >= $endTimeStamp || $id == sizeof($dataArrayDayTimes) -1) {
        $averageValue = round(array_sum($intervalValues) / count($intervalValues));
        // save the average value for interval start time in the usual format
        $arrayFormat = array();
        $arrayFormat[$timeColumnName] = date('Y-m-d H:i:s', $startTimeStamp);
        if ($isNewDay) {
            $arrayFormat['isNewDay'] = true;
            $isNewDay = false;
        } else {
            $arrayFormat['isNewDay'] = false;
        }
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
            /** @var DateTime $currentDateTime */
            $currentDateTime = new DateTime(date('Y-m-d H:i:s', $startTimeStamp));
            $newDay = $currentDateTime->add(date_interval_create_from_date_string('1 day'));
            $newDay->setTime($dayStartHour, 0);
            $startTimeStamp = $newDay->getTimestamp();
            $endTimeStamp = $startTimeStamp + $intervalDuration;
            $isNewDay = true;
        }

        $isNewDay = false;
        $intervalValues = array();
    }

    // date in data changed, reset interval times to new data
    if (date('d', $valuePairTimeStamp) !== $currentDay) {
        $currentDay = date('d', $valuePairTimeStamp);
        $startTimeStamp = $valuePairTimeStamp;
        $endTimeStamp = $startTimeStamp + $intervalDuration;
        $isNewDay = true;
    }

    // TODO es fehlen teilweise werte in der original datei
    // adding only timeValuePairs from inside the interval to inv
    if ($valuePairTimeStamp >= $startTimeStamp && $valuePairTimeStamp < $endTimeStamp) {
        array_push($intervalValues, intval($timeValuePair[$valueColumnName]));
    }

    // there are some "holes" in the data stream, fast forward to next available datapoint
    if ($valuePairTimeStamp > $endTimeStamp && $intervalValues === []) {
        //var_dump("hole in the dataset at", $timeValuePair);
        $startTimeStamp = $valuePairTimeStamp;
        $endTimeStamp = $startTimeStamp + $intervalDuration;
        continue;
    }


}


/*// add latest 15 values as detailed values
for ($i = (count($dataArrayDayTimes) - $detailedNewItemsCount); $i <= count($dataArrayDayTimes)-1; $i++) {
    $timeValuePair = $dataArrayDayTimes[$i];
    unset($timeValuePair['isNewDay']);
    $valuePairTime = new DateTime($timeValuePair[$timeColumnName]);
    $valuePairTimeStamp = $valuePairTime->getTimestamp();
    $valuePairTimeStamp = date('H:i', $valuePairTimeStamp);

    $arrayFormat = array();
    // add detailed time stamp to every only for every fourth value (for readability of labels)
    if ($i % 5 === 0) {
        if(intval($timeValuePair[$valueColumnName]) !== 1) {
            // add "en" to "Person", if more than 1 person is present
            $arrayFormat[$timeColumnName] = $valuePairTimeStamp . ' - ' . $timeValuePair[$valueColumnName] . ' Personen';
        } else {
            $arrayFormat[$timeColumnName] = $valuePairTimeStamp . ' - ' . $timeValuePair[$valueColumnName] . ' Person';
        }
    } else {
        $arrayFormat[$timeColumnName] = ' ';
    }
    $arrayFormat[$valueColumnName] = $timeValuePair[$valueColumnName];

    // add summarized value to filtered values
    array_push($filteredValues, $arrayFormat);
}*/


/*
var_dump("gefilterte Werte");
var_dump(count($filteredValues));
var_dump($filteredValues[count($filteredValues)-1]);
var_dump($dataArray[count($dataArray)-1]);
*/
// close source csv file
fclose($fp);

// open new csv file for filtered values
$txtFileName = 'paxcounter_data_filtered.csv';//
$fp = fopen($txtFileName, 'wb');
$newCsvLine = ['time', 'value'];
fputcsv( $fp , $newCsvLine);

foreach ($filteredValues as $id => $timeValuePair) {
    if ($timeValuePair['isNewDay']) {
        $valuePairTime = new DateTime($timeValuePair[$timeColumnName]);
        $valuePairTimeStamp = $valuePairTime->getTimestamp();
        $date = date('F-d', $valuePairTimeStamp);
        $timeValuePair[$timeColumnName] = $date;
    } else {
        // set date label only for every new day
        $timeValuePair[$timeColumnName] = ' ';
    }
    $newCsvLine = [$timeValuePair[$timeColumnName], $timeValuePair[$valueColumnName]];
    fputcsv($fp, $newCsvLine);
}
fclose($fp);

