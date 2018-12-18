<?php

namespace Goulaheau\RestBundle\Entity;

class QueryParams
{
    public $groups = [];

    public $attributes = [];

    public function __construct($queryParams = [])
    {
        if (isset($queryParams['_g'])) {
            $this->groups = $this->stringToArray($queryParams['_g']);
        }

        if (isset($queryParams['_a'])) {
            $this->attributes = $this->stringToArray($queryParams['_a'], true);
        }
    }

    protected function stringToArray(string $string, $hasRelations = false)
    {
        $array = explode(',', $string);

        if (!$hasRelations) {
            return $array;
        }

        $relations = [];

        foreach ($array as $key => $value) {
            if (!strpos($value, '.')) {
                continue;
            }

            $this->dotStringToArray($relations, $value);

            unset($array[$key]);
        }

        $array = array_values($array);
        $array = array_merge($array, $relations);

        return $array;
    }

    protected function dotStringToArray(&$array, $value)
    {
        $keys = explode('.', $value);

        foreach ($keys as $i => $key) {
            if ($i === count($keys) - 1) {
                $array = &$array[];
                continue;
            }

            $array = &$array[$key];
        }

        $array = $keys[count($keys) - 1];
    }
}
