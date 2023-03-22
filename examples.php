<?php
require_once(__DIR__ . '/vendor/autoload.php');

use Frostybee\Geobee\Calculator;

/*
 * Calculate distance from downtown Montreal to Laval
 * From: Ville-Marie (postal code area: H3A)
 *       Latitude/Longitude: 45.4987, -73.5703
 * To: Laval (postal code area: H7T) 
 *     Latitude/Longitude: 45.55690, -73.7480
 */
$from_latitude = 45.4987;
$from_longitude = -73.5703;
$to_latitude = 45.55690;
$to_longitude = -73.7480;

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
// Examples of single conversions:
echo $calculator->to('mi', 3, false); // False to round UP, true, round down.
echo '<br>';
echo 'Rounded: ';
echo $calculator->to('km', 2, false); // False to round UP, true, round down.

echo '<br>';
// Convert to all supported length units.
$results = $calculator->toAll(2, true);
//echo $calculator->to('nm', 5, false);
var_dump($results);
// Convert to multiple (one or more).
$results = $calculator->toMany(['km', 'mi'], 3, true);
var_dump($results);

// Test validation method.
$is_valid = $calculator->is_coordinate($from_latitude, $from_longitude);
var_dump($is_valid);