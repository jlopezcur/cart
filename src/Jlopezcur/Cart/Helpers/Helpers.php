<?php
namespace Jlopezcur\Cart\Helpers;

class Helpers {

    public static function normalizePrice($price) {
        return (is_string($price)) ? floatval($price) : $price;
    }

    public static function isMultiArray($array, $recursive = false) {
        if($recursive) return (count($array) == count($array, COUNT_RECURSIVE)) ? false : true;
        else {
            foreach ($array as $k => $v) {
                if (is_array($v)) return true;
                return false;
            }
        }
    }

    public static function issetAndHasValueOrAssignDefault(&$var, $default = false) {
        if ((isset($var)) && ($var!='')) return $var;
        return $default;
    }
}
