<?php

declare(strict_types=1);

namespace Frostybee\Geobee {

    use Exception;

    /**
     * Calculates the great-circle distance between geographical coordinates using the Haversine formula.
     *
     * @since 1.0.1
     */
    class Calculator
    {
        /**
         * Validation regex for latitude coordinates (-90 to +90 degrees).
         *
         * @var string
         */
        const REGEX_LATITUDE = '/^(\+|-)?(?:90(?:(?:\.0{1,6})?)|(?:[0-9]|[1-8][0-9])(?:(?:\.[0-9]{1,6})?))$/';

        /**
         * Validation regex for longitude coordinates (-180 to +180 degrees).
         *
         * @var string
         */
        const REGEX_LONGITUDE = '/^(\+|-)?(?:180(?:(?:\.0{1,6})?)|(?:[0-9]|[1-9][0-9]|1[0-7][0-9])(?:(?:\.[0-9]{1,6})?))$/';

        /**
         * Earth's mean radius in meters (WGS-84 ellipsoid).
         *
         * @var int
         */
        private const EARTH_RADIUS = 6378137;

        /**
         * Supported length units with conversion factors to meters.
         *
         * @var array<string, array{conversion: float}>
         */
        private const CONVERSION_UNITS = [
            // Meter - base unit for distance
            "m" => ["conversion" => 1],
            // Kilometer: 1km = 1000 meters
            "km" => ["conversion" => 1000],
            // Feet: 1ft = 0.3048 meters
            "ft" => ["conversion" => 0.3048],
            // Yard: 1yd = 0.9144 meters
            "yd" => ["conversion" => 0.9144],
            // Mile: 1mi = 1609.344 meters
            "mi" => ["conversion" => 1609.344],
            // Nautical mile: 1nm = 1852 meters
            "nm" => ["conversion" => 1852],
        ];

        /**
         * Computed distance between two points in meters.
         *
         * @var float
         */
        private float $distance = 0;

        /**
         * Calculates the great-circle distance between two points using the Haversine formula.
         *
         * @param float $from_latitude  Starting latitude in decimal degrees (-90 to +90).
         * @param float $from_longitude Starting longitude in decimal degrees (-180 to +180).
         * @param float $to_latitude    Destination latitude in decimal degrees (-90 to +90).
         * @param float $to_longitude   Destination longitude in decimal degrees (-180 to +180).
         * @return $this
         * @throws InvalidCoordinateFormatException If coordinates are invalid.
         */
        public function calculate(
            float $from_latitude,
            float $from_longitude,
            float $to_latitude,
            float $to_longitude,
        ): self {
            if (
                !$this->isCoordinate($from_latitude, $from_longitude) ||
                !$this->isCoordinate($to_latitude, $to_longitude)
            ) {
                throw new InvalidCoordinateFormatException('The format of the provided coordinates is not valid.');
            }
            if (($from_latitude === $to_latitude) && ($from_longitude === $to_longitude)) {
                $this->distance = 0.0;
                return $this;
            }

            // Convert from degree to radian.
            $from_lat_rad = deg2rad($from_latitude);
            $from_long_rad = deg2rad($from_longitude);
            $to_lat_rad = deg2rad($to_latitude);
            $to_long_rad = deg2rad($to_longitude);
            $longitude_delta = $to_long_rad - $from_long_rad;

            $alpha = (cos($to_lat_rad) * sin($longitude_delta)) ** 2 +
                (cos($from_lat_rad) * sin($to_lat_rad) - sin($from_lat_rad) * cos($to_lat_rad) * cos($longitude_delta)) ** 2;
            $beta = sin($from_lat_rad) * sin($to_lat_rad) + cos($from_lat_rad) * cos($to_lat_rad) * cos($longitude_delta);

            $angle = atan2(sqrt($alpha), $beta);
            // Compute the distance in meters.
            $this->distance = $angle * static::EARTH_RADIUS;

            return $this;
        }

        /**
         * Converts the computed distance to the specified unit.
         *
         * @param string   $unit     Unit symbol (m, km, ft, yd, mi, nm).
         * @param int|null $decimals Number of decimal places (1-9), or null for no rounding.
         * @param bool     $round    True to round up, false to round down.
         * @return float|int The converted distance.
         * @throws InvalidUnitException If the unit is invalid or unsupported.
         */
        public function to(string $unit, ?int $decimals = null, bool $round = true): float|int
        {
            $result = 0;
            if ($this->distance === 0) {
                return 0;
            }

            $unit = strtolower($unit);

            if (!$this->unitExists($unit)) {
                throw new InvalidUnitException(
                    "Unsupported unit '$unit'. Supported units: " . implode(', ', array_keys(self::CONVERSION_UNITS))
                );
            }

            $conversion = $this->getConversion($unit);
            if (is_numeric($conversion)) {
                $result =  $this->distance / $conversion;
                if ($this->isPrecisionValid($decimals)) {
                    $result =  $this->round($result, $decimals, $round);
                }
            }
            return $result;
        }

        /**
         * Converts the computed distance to multiple units.
         *
         * @param array    $units    Array of unit symbols.
         * @param int|null $decimals Number of decimal places (1-9), or null for no rounding.
         * @param bool     $round    True to round up, false to round down.
         * @return array<string, float|int> Unit symbols as keys, converted distances as values.
         * @throws InvalidUnitException If any unit is invalid or unsupported.
         */
        public function toMany(array $units = [], ?int $decimals = null, bool $round = true): array
        {
            if ($this->distance === 0) {
                return [];
            }

            $results = array();
            foreach ($units as $unit) {
                $results[$unit] = $this->to($unit, $decimals, $round);
            }
            return $results;
        }

        /**
         * Converts the computed distance to all supported units.
         *
         * @param int|null $decimals Number of decimal places (1-9), or null for no rounding.
         * @param bool     $round    True to round up, false to round down.
         * @return array<string, float|int> Unit symbols as keys, converted distances as values.
         * @throws InvalidUnitException If conversion fails.
         */
        public function toAll(?int $decimals = null, bool $round = true): array
        {
            if ($this->distance === 0) {
                //  No conversion to be performed: the distance hasn't been computed yet.
                return [];
            }
            // Apply the conversion to all defined length units.
            return $this->toMany(array_keys(self::CONVERSION_UNITS), $decimals, $round);
        }

        /**
         * Validates that decimal precision is between 1 and 9.
         *
         * @param int|null $decimals Number of decimal places.
         * @return bool True if valid, false otherwise.
         */
        private function isPrecisionValid(?int $decimals): bool
        {
            return $decimals !== null && $decimals <= 9 && $decimals > 0;
        }
        /**
         * Retrieves the conversion factor for the specified unit.
         *
         * @param string $unit Unit symbol (lowercase).
         * @return float Conversion factor to meters.
         */
        private function getConversion(string $unit): float
        {
            return self::CONVERSION_UNITS[$unit]['conversion'];
        }
        /**
         * Checks if a unit of measurement is supported.
         *
         * @param string $unit Unit symbol to check.
         * @return bool True if supported, false otherwise.
         */
        private function unitExists(string $unit): bool
        {
            return array_key_exists($unit, self::CONVERSION_UNITS);
        }

        /**
         * Rounds a value to the specified precision.
         *
         * @param float $value    Value to round.
         * @param int   $decimals Number of decimal places.
         * @param bool  $round    True for HALF_UP, false for HALF_DOWN.
         * @return float Rounded value.
         */
        private function round(float $value, int $decimals, bool $round): float
        {
            $mode = $round ? PHP_ROUND_HALF_UP : PHP_ROUND_HALF_DOWN;
            return round($value, $decimals, $mode);
        }
        /**
         * Gets the computed distance in meters.
         *
         * @return float Distance in meters.
         * @see to()
         */
        public function getDistance(): float
        {
            return $this->distance;
        }
        /**
         * Validates a pair of latitude and longitude coordinates.
         *
         * @param mixed $latitude  Latitude value to validate.
         * @param mixed $longitude Longitude value to validate.
         * @return bool True if both are valid, false otherwise.
         */
        public function isCoordinate(mixed $latitude, mixed $longitude): bool
        {
            return $this->isLatitude($latitude) && $this->isLongitude($longitude);
        }

        /**
         * Validates a latitude value.
         *
         * @param mixed $latitude Latitude value to validate.
         * @return bool True if valid (-90 to +90), false otherwise.
         */
        private function isLatitude(mixed $latitude): bool
        {
            if (is_array($latitude)) {
                return false;
            }
            return preg_match(static::REGEX_LATITUDE, (string) $latitude) === 1;
        }

        /**
         * Validates a longitude value.
         *
         * @param mixed $longitude Longitude value to validate.
         * @return bool True if valid (-180 to +180), false otherwise.
         */
        private function isLongitude(mixed $longitude): bool
        {
            if (is_array($longitude)) {
                return false;
            }
            return preg_match(static::REGEX_LONGITUDE, (string) $longitude) === 1;
        }
    }

    /**
     * Exception thrown when coordinate format validation fails.
     */
    class InvalidCoordinateFormatException extends Exception
    {
    }

    /**
     * Exception thrown when an invalid or unsupported unit is provided.
     */
    class InvalidUnitException extends Exception
    {
    }
}
