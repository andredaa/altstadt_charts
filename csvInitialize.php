<?php


// open new csv file
$txtFileName = 'paxcounter_data_filtered.csv';//
$fp = fopen($txtFileName, 'wb');
$newCsvLine = ['time', 'value'];
fputcsv( $fp , $newCsvLine);
fclose($fp);
