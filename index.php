<?php
require_once(__DIR__ . '/vendor/autoload.php');

use Frostybee\Geobee\Calculator;

// H3S,QC,45.5155,-73.6292
// H3T,QC,45.5115,-73.616
// H3V,QC,45.4965,-73.6177
$from_latitude = 45.5155;
$from_longitude = -73.6292;
$to_latitude = 45.4965;
$to_longitude = -73.6177;

$calculator = new Calculator();
$distance = $calculator->calculate(
    $from_latitude,
    $from_longitude,
    $to_latitude,
    $to_longitude
)->to('km');

echo '<pre>';
echo '<br>';
echo $distance;
echo '<br>';

$distance = $calculator->calculate(
    $from_latitude,
    $from_longitude,
    $to_latitude,
    $to_longitude
)->getDistance();
echo '<br>';
// Distance in meters.
echo $distance;
echo '<br>';
// Get the computed distance.
//echo $calculator->getDistance();

// Examples of single conversions:
echo $calculator->to('mi', 3, false); // False to round UP, true, round down.
echo '<br>';
echo 'Rounded: ';
echo $calculator->to('km', 2, false); // False to round UP, true, round down.
echo '<br>';
echo round(26.91310884, 6,  PHP_ROUND_HALF_UP);
echo '<br>';
// Convert to all supported length units.
$results = $calculator->toAll(2, true);
//echo $calculator->to('nm', 5, false);
var_dump($results);
// Convert to multiple (one or more).
$results = $calculator->toMany(['km', 'mi'], 3, true);
var_dump($results);
