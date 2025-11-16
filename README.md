# Geobee

A standalone PHP library for calculating the great-circle distance between geographical coordinates using a spherical Earth model.

## Installation

This library requires no dependencies.

To install it:

* Clone this repository.
* Or use composer: `composer require frostybee/geobee`

## Units of Lengths

The following are the supported units of lengths along with their respective conversion factors:
* `m` - Meters (default, base unit)
* `km` - Kilometers (1km = 1000 meters)
* `mi` - Miles (1mi = 1609.344 meters)
* `nm` - Nautical miles (1nm = 1852 meters)
* `yd` - Yards (1yd = 0.9144 meters)
* `ft` - Feet (1ft = 0.3048 meters)

**Note**: Unit symbols are case-insensitive (e.g., 'km', 'KM', 'Km' all work).

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
 *     Latitude/Longitude: 45.5569, -73.7480
 */
$calculator = new Calculator();
$distance = $calculator->calculate(
    45.4987,   // From latitude
    -73.5703,  // From longitude
    45.5569,   // To latitude
    -73.7480   // To longitude
)->getDistance(); // Returns distance in meters (e.g., 15297.83)
```

### Converting the Distance from Meters to other Units

#### Single Conversion

Use the `to()` method as show below. However, you must call the `calculate()` method once before doing any conversions.

```php
use Frostybee\Geobee\Calculator;

$calculator = new Calculator();
$distance = $calculator->calculate(
    45.4987,
    -73.5703,
    45.5569,
    -73.7480
)->to('km'); // Returns distance in kilometers (e.g., 15.298)
```

You can also specify precision and rounding behavior:

```php
use Frostybee\Geobee\Calculator;

$calculator = new Calculator();
// Syntax: to(unit, decimals, round)
$distance = $calculator->to('mi', 3, false);  // 3 decimals, use floor rounding
$distance = $calculator->to('km', 2);         // 2 decimals, default round (true)
```

Parameters for `to()` method:
- `$unit` (string, required): Unit symbol for conversion
- `$decimals` (int|null, optional): Number of decimal places (1-9), null for no rounding
- `$round` (bool, optional, default=true): true = standard rounding, false = floor rounding

#### Batch Conversions

Convert the distance to multiple units efficiently:

```php
use Frostybee\Geobee\Calculator;

$calculator = new Calculator();
$distance = $calculator->calculate(45.4987, -73.5703, 45.5569, -73.7480);

// Convert to specific units using toMany()
$results = $calculator->toMany(['km', 'mi'], 3, true);
/*
Returns:
array(2) {
  ["km"] => float(15.298)
  ["mi"] => float(9.506)
}
*/

// Convert to all supported units using toAll()
$distance_in_all_units = $calculator->toAll(2, true);
/*
Returns:
array(6) {
  ["m"]  => float(15297.83)
  ["km"] => float(15.30)
  ["ft"] => float(50189.72)
  ["yd"] => float(16729.91)
  ["mi"] => float(9.51)
  ["nm"] => float(8.26)
}
*/
```

## Coordinate Validation

The library provides a public method to validate latitude/longitude coordinates before performing calculations.

### Using `isCoordinate()` Method

```php
use Frostybee\Geobee\Calculator;

$calculator = new Calculator();

// Validate coordinates before calculation
if ($calculator->isCoordinate(45.4987, -73.5703)) {
    $distance = $calculator->calculate(45.4987, -73.5703, 45.5569, -73.7480)
                          ->to('km');
    echo "Distance: $distance km";
} else {
    echo "Invalid coordinates provided.";
}
```

### Coordinate Requirements

- **Latitude**: Must be between -90 and +90 (decimal degrees)
- **Longitude**: Must be between -180 and +180 (decimal degrees)
- Both values can have up to 6 decimal places
- Optional +/- sign is allowed
- Arrays are not accepted as coordinate values

### Validation Examples

```php
$calculator->isCoordinate(45.4987, -73.5703);   // Valid: true
$calculator->isCoordinate(91, 50);              // Invalid: latitude > 90
$calculator->isCoordinate(45, 181);             // Invalid: longitude > 180
$calculator->isCoordinate("45.5", "-73.7");     // Valid: strings are accepted
$calculator->isCoordinate([45], [73]);          // Invalid: arrays not allowed
```

## Error Handling

The library throws exceptions for invalid input:
- **`InvalidCoordinateFormatException`**: When coordinates fail validation
- **`InvalidUnitException`**: When an invalid or unsupported unit is specified

```php
use Frostybee\Geobee\Calculator;
use Frostybee\Geobee\InvalidCoordinateFormatException;
use Frostybee\Geobee\InvalidUnitException;

try {
    $calculator = new Calculator();
    $distance = $calculator->calculate(45.4987, -73.5703, 45.5569, -73.7480)
                          ->to('km', 2);
    echo "Distance: $distance km";
} catch (InvalidCoordinateFormatException $e) {
    echo "Invalid coordinates: " . $e->getMessage();
} catch (InvalidUnitException $e) {
    echo "Invalid unit: " . $e->getMessage();
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
```

## Advanced Usage

### Method Chaining

The `calculate()` method returns the Calculator instance, enabling fluent method chaining:

```php
use Frostybee\Geobee\Calculator;


$calculator = new Calculator();
// Chain multiple operations
$km = $calculator->calculate(45.4987, -73.5703, 45.5569, -73.7480)->to('km', 2);
$mi = $calculator->to('mi', 2);  // Reuse the same calculation
$nm = $calculator->to('nm', 2);
```

### Edge Cases

#### Zero Distance (Same Coordinates)

When calculating distance from a point to itself:

```php
use Frostybee\Geobee\Calculator;

$calculator = new Calculator();
$distance = $calculator->calculate(45.5, -73.7, 45.5, -73.7)->getDistance();
// Returns: 0

$km = $calculator->to('km');
// Returns: 0

$all = $calculator->toAll();
// Returns: [] (empty array)
```

**Note**: `toAll()` returns an empty array when distance is zero, while `to()` returns 0.

### Precision Control

Control the precision of converted distances:

```php
use Frostybee\Geobee\Calculator;

$calculator = new Calculator();
$calculator->calculate(45.4987, -73.5703, 45.5569, -73.7480);

// No rounding (returns full precision)
$distance = $calculator->to('km');  // e.g., 15.29783...

// With decimals (1-9 allowed)
$distance = $calculator->to('km', 2);      // e.g., 15.30
$distance = $calculator->to('km', 5);      // e.g., 15.29783

// Rounding behavior
$distance = $calculator->to('km', 2, true);   // Standard rounding (PHP_ROUND_HALF_UP)
$distance = $calculator->to('km', 2, false);  // Floor rounding (PHP_ROUND_HALF_DOWN)
```

## API Reference

### Calculator Class Methods

| Method | Parameters | Return Type | Description |
|--------|-----------|-------------|-------------|
| `calculate()` | `float $from_lat, float $from_lon, float $to_lat, float $to_lon` | `self` | Calculates distance between two coordinates. Returns `$this` for chaining. |
| `getDistance()` | None | `float` | Returns the calculated distance in meters. |
| `to()` | `string $unit, ?int $decimals = null, bool $round = true` | `float\|int` | Converts distance to specified unit. |
| `toMany()` | `array $units = [], ?int $decimals = null, bool $round = true` | `array` | Converts distance to multiple units. Returns associative array. |
| `toAll()` | `?int $decimals = null, bool $round = true` | `array` | Converts distance to all supported units. Returns associative array. |
| `isCoordinate()` | `mixed $latitude, mixed $longitude` | `bool` | Validates a coordinate pair. Returns true if valid. |

### Implementation Details

- **Algorithm**: Great-circle distance using spherical Earth model (numerically stable implementation with `atan2()`)
- **Earth Radius**: 6,378,137 meters (WGS84 semi-major axis)
- **Coordinate Format**: Decimal degrees with up to 6 decimal places
- **Precision**: Suitable for most geographic distance calculations; for higher precision on an ellipsoidal model, consider using a full geodesic library

## License

Geobee is an open-source library licensed under the [MIT LICENSE](LICENSE).
