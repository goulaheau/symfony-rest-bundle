<?php

namespace Goulaheau\RestBundle\Core;

class Utils
{
    private function __construct()
    {
    }

    public static function classNameToLowerCase($className)
    {
        $array = explode('\\', $className);

        foreach ($array as &$value) {
            preg_match_all('!([A-Z][A-Z0-9]*(?=$|[A-Z][a-z0-9])|[A-Za-z][a-z0-9]+)!', $value, $matches);

            $ret = $matches[0];

            foreach ($ret as &$match) {
                $match = $match == strtoupper($match) ? strtolower($match) : lcfirst($match);
            }

            $value = implode('-', $ret);
        }

        return implode('.', $array);
    }
}