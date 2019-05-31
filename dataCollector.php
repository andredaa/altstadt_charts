<?php
/**
* get Request on data urls
*  filter each response for newest value
*  send all values json encoded to the ttn
* done
*/


// set local for time
setlocale(LC_TIME, "de_DE");


/**
* data https://api.opensensemap.org/boxes/5ca9f885695e3b001ac9245c/sensors
*
* box ids
* hafencity: 590e0b0a51d3460011c725c4
* altstadt: 5ca9f885695e3b001ac9245c
* st. georg: 5b816cce7c51910019888ed6
*
* todo : umweltbundesamt stresemannstrasse
*
*/
function getFeinstaubValue()
{

$baseUrl = 'https://api.opensensemap.org/boxes/';
$boxIds = ['590e0b0a51d3460011c725c4', '5ca9f885695e3b001ac9245c', '5b816cce7c51910019888ed6'];

$values = [];

foreach ($boxIds as $boxId) {
$url = $baseUrl . $boxId . '/sensors';
$response = file_get_contents($url);
$responseJson = json_decode($response, true);
$lastValue = floatval($responseJson['sensors'][0]['lastMeasurement']['value']);
$values[] = $lastValue;
}

return intval(max($values));
}


/**
* Abgaswerte NO2 auf der Stresemannstrasse (höchste Belastung in HH) [Source UBA]
*
* @return float
*/
function getNo2Value()
{

$timeNow = new DateTime('now');

$timeStampNow = $timeNow->getTimestamp();
// creating a unix timestamp for the time 30min ago
$timeStampPast = $timeStampNow - 120 * 60;

// curl "http://www.example.com/?test\[\]=123"
$baseURL = 'https://www.umweltbundesamt.de/js/uaq/data/stations/measuring?station[]=DEHH026&pollutant[]=NO2&scope[]=1SMW&group[]=pollutant&range[]=';

$requestUrl = $baseURL . $timeStampPast . ',' . $timeStampNow;

var_dump($requestUrl);

$response = file_get_contents($requestUrl);

if ($response === false) {
var_dump("Exhaust URL not working");

return 0;
}

$responseJson = json_decode($response, true);
$data = $responseJson['data'][0];

foreach ($data as $value) {
    if ($value[0] != false) {

        return intval($value[0]);
    }
}

return 0;
}


function getPaxCounterFromLastPostTxt()
{


// die geposteten Daten sollten aus der Datei gelesen werden
// die geposteten Daten sollten aus der Datei gelesen werden
$txtFileName = 'http://gut-verdrahtet.de/lastpost.txt';

$fp = fopen($txtFileName, 'r');
$ttnpost = fgets($fp);
fclose($fp);

if ($ttnpost === false) {
    echo "Kann lastpost.txt nicht öffnen";
    exit;
}

return intval(str_replace('paxcount":', "", strstr(strstr($ttnpost, 'paxcount'), '}', true)));

}

function postData() {

    $url = 'https://integrations.thethingsnetwork.org/ttn-eu/api/v2/down/klimaprobe/sensor-data?key=ttn-account-v2.Ymn1B5VcoYbV0NjZ__r9pEUdfqb6AK-s5wHGCe3Hn3U';

    /**
    $sensorData = '{' . '"feinstaub": ' . getFeinstaubValue() . ', '
    . '"stickoxid": ' . getNo2Value() . ', '
    . '"pax": ' . getPaxCounterFromLastPostTxt()
    ;
    */

    $sensorData = get_base_16_encoded_result();

    $dataJson = '{"dev_id": "rak811-2",
    "port": 1,
    "confirmed": false,
    "payload_raw": "' . $sensorData . '"}';

    var_dump($dataJson);

    $options = array(
    'http' => array(
    'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
    'method'  => 'POST',
    'content' => $dataJson
    )
    );

    $context  = stream_context_create($options);
    $result = file_get_contents($url, false, $context);
    if ($result === FALSE) {
        var_dump($result);
        var_dump("something wrong");
        /* Handle error */
    }

    var_dump($result);
}

/**
 * @return string
 */
function get_base_16_encoded_result() {

    $feinstaub = getFeinstaubValue();
    $stick = getNo2Value();
    $pax = getPaxCounterFromLastPostTxt();

    $raw_value_string = '';
    foreach ([$feinstaub, $stick, $pax] as $value) {
      //  var_dump("normal", $value);
      //  var_dump("hex", base_convert($value, 10, 16));
        $raw_value = base_convert($value, 10, 16);
        if ($value <= 16) {
            $raw_value = '0' . $raw_value;
       //     var_dump("null angehangen", $raw_value);
        }

        $raw_value_string = $raw_value_string . $raw_value;
    }
  //*/

    var_dump($raw_value_string);
    return $raw_value_string;
}

//postData();

postData();