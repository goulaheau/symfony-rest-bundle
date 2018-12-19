<?php

namespace Goulaheau\RestBundle\Utils;

class RestQueryParams
{
    public $attributes = [];

    public $groups = [];

    public $entityFunctions = [];

    public $repositoryFunctions = [];

    public function __construct($queryParams = [])
    {
        if (isset($queryParams['_a'])) {
            $this->attributes = $this->stringToArray($queryParams['_a'], true);
            unset($queryParams['_a']);
        }

        if (isset($queryParams['_g'])) {
            $this->groups = $this->stringToArray($queryParams['_g']);
            unset($queryParams['_g']);
        }

        if (isset($queryParams['_ef'])) {
            $this->entityFunctions = $this->stringToFunctionsArray($queryParams['_ef']);
            unset($queryParams['_ef']);
        }

        if (isset($queryParams['_efp'])) {
            $this->setFunctionsParameters($queryParams['_efp'], $this->entityFunctions);
            unset($queryParams['_efp']);
        }

        if (isset($queryParams['_rf'])) {
            $this->repositoryFunctions = $this->stringToFunctionsArray($queryParams['_rf']);
            unset($queryParams['_rf']);
        }

        if (isset($queryParams['_rfp'])) {
            $this->setFunctionsParameters($queryParams['_rfp'], $this->repositoryFunctions);
            unset($queryParams['_rfp']);
        }
    }

    protected function setFunctionsParameters($params, &$functions)
    {
        foreach ($params as $index => $param) {
            if (!isset($functions[$index])) {
                continue;
            }

            $functions[$index]['parameters'] = $param;
        }
    }

    protected function stringToFunctionsArray(string $string)
    {
        $array = $this->stringToArray($string);

        $newArray = [];

        foreach ($array as $value) {
            $newArray[] = [
                'function' => $value,
                'parameters' => [],
            ];
        }

        return $newArray;
    }

    protected function stringToArray(string $string, ?bool $hasRelations = false)
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
