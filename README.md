# Geobee

A standalone PHP library for calculating the distance between geographical coordinates.

## Installation

This library requires no dependencies.

To install it:

* Clone this repository.
* Or use composer: `composer require frostybee/geobee`

## Units of Lengths

The following are the supported units of lengths along with their respective  conversion factors.
* `m` - Meters (default)
* `km` - kilometers (1km = 1000 meters)
* `mi` - Miles (1mi = 1609.344 meters)
* `nm` - Nautical miles (1nm = 1852 meters)
* `yd` - Yards (1yd = 0.9144)
* `ft` - Feet (1ft = 0.3048 meters)

## Usage

Using this library is simple and straightforward. Just instantiate the `Calculator` class and supply two pairs of latitude/longitude to the `calculate()` method as shown below.

```php
<?php

use Frostybee\Geobee\Calculator;

/*
 * Calculate the distance from downtown Montreal to Laval.
 * From: Ville-Marie 
 *       postal code area: H3A
 *       Latitude/Longitude: 45.4987, -73.5703
 * To: Laval 
 *     postal code area: H7T 
 *     Latitude/Longitude: 45.55690, -73.7480
 */
$calculator = new Calculator();
$distance = $calculator->calculate(
    $from_latitude,
    $from_longitude,
    $to_latitude,
    $to_longitude
)->getDistance(); // Distance in meters.
```

### Converting the Distance from Meters to Another Unit

Use the `to()` method as show below:

``` php
$distance = $calculator->calculate(
    $from_latitude,
    $from_longitude,
    $to_latitude,
    $to_longitude
)->to('km'); // Get the distance in kilometers.
```

Or:

```php
$distance = $calculator->to('mi', 3, false);
```

Convert to all supported length units.

```php
//$calculator->toAll($decimals, $round);
$results = $calculator->toAll(2, true);
```

output:

```php
array(6) {
  ["m"]=>
  float(15297.83)
  ["km"]=>
  float(15.3)
  ["ft"]=>
  float(50189.72)
  ["yd"]=>
  float(16729.91)
  ["mi"]=>
  float(9.51)
  ["nm"]=>
  float(8.26)
}
```

Convert to multiple (one or more) units of lengths.

```php
// $calculator->toMany(array, $decimals, $round);
$results = $calculator->toMany(['km', 'mi'], 3, true);
```

output:

```php
array(2) {
  ["km"]=>
  float(15.298)
  ["mi"]=>
  float(9.506)
}
```
