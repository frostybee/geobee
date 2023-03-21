<?php

namespace Frostybee\Geobee;

use Exception;

/**
 * 
 * @version 1:1.0.0
 */
class Calculator
{
    private const EARTH_RADIUS = 6378137;

    /**
     * Summary of conversion_units
     * @var array
     */
    private $conversion_units = array(
        //meter - base unit for distance
        "m" => array("base" =>  "m", "conversion" => 1),
        //kilometer
        "km" => array("base" => "m", "conversion" => 1000),
        //foot
        "ft" => array("base" => "m", "conversion" => 0.3048),
        //yard
        "yd" => array("base" => "m", "conversion" => 0.9144),
        //mile                        
        "mi" => array("base" => "m", "conversion" => 1609.344),
        // Nautical mile                 
        "nm" => array("base" => "m", "conversion" => 1852),
    );

    /**
     * Holds the computed distance between two points in meters.
     * @var float
     */
    private float $distance = 0;

    /**
     * !Summary of calculate
     * @param float $from_latitude
     * @param float $from_longitude
     * @param float $to_latitude
     * @param float $to_longitude     
     * @return DistanceHelper|int
     */
    public function calculate(
        float $from_latitude,
        float $from_longitude,
        float $to_latitude,
        float $to_longitude,
    ) {
        if (($from_latitude == $to_latitude) && ($from_longitude == $to_longitude)) {
            return 0;
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
        // TODO:         
        // *     -add support for rounding and decimal points
        $result = 0;
        if ($this->distance == 0) {
            //throw new ConvertorException("From Value Not Set.");
            return 0;
        }
        if (is_array($unit)) {
            // Convert to many target units.
            //return $this->toMany($unit, $decimals, $round);
        }
        if (!$this->unitExists($unit)) {
            //echo 'Doesn\'t Exist';            
            throw new Exception("Conversion from Unit u=$unit not possible - unit does not exist.");
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
}
