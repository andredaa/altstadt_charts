<?php

setlocale(LC_TIME, "de_DE");

//$timeNow = new DateTime('now');
$timeNow = new DateTime('now');
$date = date('Y-m-d', $timeNow->getTimestamp());
$time = date('H:i:s', $timeNow->getTimestamp());

var_dump($date, $time); exit;




$timeStampNow = $timeNow->getTimestamp();

$humanTimestamp = new DateTime(date('Y-m-d H:i:s', $timeStampNow));

var_dump($humanTimestamp);
exit;