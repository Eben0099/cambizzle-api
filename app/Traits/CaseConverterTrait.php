<?php

namespace App\Traits;

trait CaseConverterTrait
{
    /**
     * Convertit camelCase en snake_case
     */
    protected function camelToSnake($data)
    {
        if (!is_array($data)) {
            return $data;
        }

        $result = [];
        foreach ($data as $key => $value) {
            $snakeKey = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $key));

            if (is_array($value)) {
                $result[$snakeKey] = $this->camelToSnake($value);
            } else {
                $result[$snakeKey] = $value;
            }
        }

        return $result;
    }

    /**
     * Convertit snake_case en camelCase
     */
    protected function snakeToCamel($data)
    {
        if (!is_array($data)) {
            return $data;
        }

        $result = [];
        foreach ($data as $key => $value) {
            $camelKey = lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $key))));

            if (is_array($value)) {
                $result[$camelKey] = $this->snakeToCamel($value);
            } else {
                $result[$camelKey] = $value;
            }
        }

        return $result;
    }
}