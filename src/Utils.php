<?php


namespace Statham;


class Utils {

    public static function isAbsoluteUri($uri){
        return preg_match('/^https?:\/\//', $uri) === 1;
    }

    public static function isRelativeUri($uri) {
        return preg_match('/.+#/', $uri) === 1;
    }

    public static function whatIs($what){

        $type = gettype($what);
        switch($type){
            case "double":
            case "integer":
                if(is_finite($what)){
                    if(is_int($what)){
                        return "integer";
                    }

                    if(self::isMultipleOf($what, 1)){
                        return "integer";
                    }

                    return "number";
                }
            
                if(is_nan($what)){
                    return "not-a-number";
                }
            
                return "unknown-number";
            case "NULL":
                return "null";
            case "boolean":
            case "object":
            case "array":
            case "string":
                return $type;
            default:
                return "unknown-type";

        }

    }

    public static function regex($pattern, $escape = true){
        return '#' . str_replace('#', '\\#', $pattern) . '#';
    }

    public static function diff(array $a, array $b){
        return array_udiff($a, $b, function($a, $b){
            if(gettype($a) !== gettype($b))
                return -1;

            return $a === $b ? 0 : 1;
        });
    }

    public static function arrayIsUnique(array $array){
        $diffed = self::diff($array, array_unique($array, SORT_REGULAR));
        return count($diffed) <= 0;
    }

    public static function isMultipleOf($value, $multiple, float $epsilon = 1e-12){
        if(!is_numeric($value) || !is_numeric($multiple)){
            return false;
        }

        $valueFloat = (float) $value;
        $multipleFloat = (float) $multiple;

        if($multipleFloat == 0.0){
            return false;
        }

        if(is_int($value) && is_int($multiple)){
            return $value % $multiple === 0;
        }

        $remainder = fmod($valueFloat, $multipleFloat);
        if(is_nan($remainder)){
            return false;
        }

        $remainder = abs($remainder);
        $multipleFloat = abs($multipleFloat);

        $tolerance = max(abs($valueFloat), $multipleFloat, 1.0) * $epsilon;

        return $remainder <= $tolerance || abs($remainder - $multipleFloat) <= $tolerance;
    }

}
