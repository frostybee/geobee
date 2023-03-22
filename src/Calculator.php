<?php

namespace Frostybee\Geobee {

    use Exception;

    /**
     * A standalone PHP library for calculating the distance between
     * geographical coordinates. 
     *      
     * The Vincenty formulae is used for calculating geodesic distances between 
     * a pair of lati­tude/longi­tude points on an ellipsoidal model of the Earth (an oblate sphere).
     * 
     * This library is useful for computing the distance between two canadian postal 
     * codes or finding nearby locations. 
     * @since 1.0.1
     */
    class Calculator
    {
        /**
         * Validation regex for Latitude parameters.
         */
        const REGEX_LATITUDE = '/^(\+|-)?(?:90(?:(?:\.0{1,6})?)|(?:[0-9]|[1-8][0-9])(?:(?:\.[0-9]{1,6})?))$/';

        /**
         * Validation regex for Longitude parameters.
         */
        const REGEX_LONGITUDE = '/^(\+|-)?(?:180(?:(?:\.0{1,6})?)|(?:[0-9]|[1-9][0-9]|1[0-7][0-9])(?:(?:\.[0-9]{1,6})?))$/';

        private const EARTH_RADIUS = 6378137;

        /**
         * Holds the list of supported units of length associated 
         * with their conversion factors relative to meters.
         * @var array
         */
        private $conversion_units = array(
            //meter - base unit for distance
            "m" => array("conversion" => 1),
            //kilometer: 1km = 1000 meters.
            "km" => array("conversion" => 1000),
            //feet: 1ft = 0.3048 meters
            "ft" => array("conversion" => 0.3048),
            //yard: 1yd =  0.9144 meters
            "yd" => array("conversion" => 0.9144),
            //mile: 1mi = 1609.344 meters.                        
            "mi" => array("conversion" => 1609.344),
            // Nautical mile: 1nm = 1852 meters                 
            "nm" => array("conversion" => 1852),
        );

        /**
         * Holds the computed distance between two points in meters.
         * @var float
         */
        private float $distance = 0;

        /**
         * Calculates the great-circle distance between two points with the Vincenty formula.
         * The latitudes and longitudes parameters must be specified in decimal degrees.
         * The valid range: 
         *                 - Latitude -90 and +90
         *                 - Latitude -180 and +180     
         * 
         * @param float $from_latitude Latitude of the start point in decimal degrees.
         * @param float $from_longitude Longitude of the starting point in decimal degrees.
         * @param float $to_latitude Latitude of the target point in decimal degrees.
         * @param float $to_longitude Longitude of the target point in decimal degrees.   
         * @return Calculator|int
         */
        public function calculate(
            float $from_latitude,
            float $from_longitude,
            float $to_latitude,
            float $to_longitude,
        ): mixed {
            if (
                !$this->is_coordinate($from_latitude, $from_longitude) ||
                !$this->is_coordinate($to_latitude, $to_longitude)
            ) {
                throw new InvalidCoordinateFormatException('The format of the provided coordinates is not valid.');
            }
            if (($from_latitude == $to_latitude) && ($from_longitude == $to_longitude)) {
                $this->distance = 0;
                return $this;
            } else {
                // Convert from degree to radian.
                $from_lat_rad = deg2rad($from_latitude);
                $from_long_rad = deg2rad($from_longitude);
                $to_lat_rad = deg2rad($to_latitude);
                $to_longitude = deg2rad($to_longitude);
                $longitude_delta = $to_longitude - $from_long_rad;
                $alpha = pow(cos($to_lat_rad) * sin($longitude_delta), 2) +
                    pow(cos($from_lat_rad) * sin($to_lat_rad) - sin($from_lat_rad) * cos($to_lat_rad) * cos($longitude_delta), 2);
                $beta = sin($from_lat_rad) * sin($to_lat_rad) + cos($from_lat_rad) * cos($to_lat_rad) * cos($longitude_delta);

                $angle = atan2(sqrt($alpha), $beta);
                // Compute the distance in meters.
                $this->distance = ($angle * static::EARTH_RADIUS);
            }
            return $this;
        }

        /**
         * Converts the computed distance to another unit.
         * @param string $unit the unit symbol (or array of symbols) for the conversion unit
         * @param int|null $decimals the desired number of digits after the decimal point. 
         * @param bool $round round or floor the converted result.
         * @return float|int
         */
        public function to(string $unit, ?int $decimals = null, bool $round = true)
        {
            $result = 0;
            if ($this->distance == 0) {
                return 0;
            }
            if (!$this->unitExists($unit)) {
                throw new Exception("The requested conversion to unit u=$unit wasn't possible:  the supplied either invalid or unsupported.");
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
         * Converts the computed distance into the specified unit(s) of length.
         * @param array $units
         * @param int|null $decimals
         * @param mixed $round
         * @return array
         */
        public function toMany(array $units = [], ?int $decimals = null, $round = true)
        {
            $results = array();
            foreach ($units as $unit) {
                $results[$unit] = $this->to($unit, $decimals, $round);
            }
            return $results;
        }

        /**
         * Converts the computed distance into all supported units of length.
         * @param int|null $decimals
         * @param bool $round
         * @return array 
         */
        public function toAll(?int $decimals = null, bool $round = true): array
        {
            if ($this->distance == 0) {
                //  No conversion to be preformed: the distance hasn't been computed yet.            
                return [];
            }
            // Apply the conversion to all defined length units.
            return $this->toMany(array_keys($this->conversion_units), $decimals, $round);
        }

        /**
         * Validates the provided number that determines how many
         * digits there should be after the decimal point. 
         * The value must be a number between 1 and 9 inclusively. 
         * @param int|null $decimals indicates the number of digits after the decimal point.
         * @return bool
         */
        private function isPrecisionValid(?int $decimals): bool
        {
            return !is_null($decimals) && $decimals <= 9 && $decimals > 0;
        }
        /**
         * Retrieves the conversation value of the provide unit of conversation.
         * @param string $unit  
         * @return float 
         */
        private function getConversion(string $unit): float
        {
            return $this->conversion_units[$unit]['conversion'];
        }
        /**
         * Determines whether a unit of measurement is supported or not. 
         * @param string $unit
         * @return bool
         */
        private function unitExists(string $unit): bool
        {
            return array_key_exists($unit, $this->conversion_units);
        }

        /**
         * Rounds a value.
         * @param float $value
         * @param int $decimals
         * @param bool $round
         * @return float
         */
        private function round(float $value, int $decimals, bool $round): float
        {
            $mode = $round ? PHP_ROUND_HALF_UP : PHP_ROUND_HALF_DOWN;
            return round($value, $decimals, $mode);
        }
        /**
         * Gets the computed distance in meters.
         * 
         * Note that the returned distance might need to be converted into 
         * another unit.
         * @see to()
         * @return float
         */
        public function getDistance(): float
        {
            return $this->distance;
        }
        /**
         * Validates a pair of latitude and longitude coordinates.
         * @param mixed $latitude
         * @param mixed $longitude
         * @return bool
         */
        public function is_coordinate(mixed $latitude, mixed $longitude)
        {
            return $this->is_latitude($latitude)  && $this->is_longitude($longitude);
        }
        /**
         * Validates a given latitude.
         *
         * @param mixed $latitude 
         * @return bool `true` if $latitude is valid, `false` if not
         */
        private function is_latitude(mixed $latitude)
        {
            return !is_array($latitude) && preg_match(static::REGEX_LATITUDE, $latitude);
        }
        /**
         * Validates a given longitude.
         *
         * @param mixed $longitude 
         * @return bool `true` if $long is valid, `false` if not
         */
        private function is_longitude(mixed $longitude)
        {
            return !is_array($longitude) && preg_match(static::REGEX_LONGITUDE, $longitude);
        }
    }
    class InvalidCoordinateFormatException extends Exception
    {
    }
}
