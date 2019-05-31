<?php
// die geposteten Daten sollten in eine Datei geschrieben werden
$txtFileName = 'lastpost.txt';
$fp = fopen($txtFileName, 'w');
$postdata=file_get_contents("php://input");

var_dump($postdata);
fwrite( $fp , $postdata);
fclose($fp);