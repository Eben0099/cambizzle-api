<?php

namespace App\Formatters;

use CodeIgniter\Format\JSONFormatter;
use Config\Format;

class CamelCaseFormatter extends JSONFormatter
{
    protected $config;

    public function __construct()
    {
        // Initialiser la configuration
        $this->config = new Format();
    }

    public function format($data)
    {
        // Vérifier si les données sont déjà une chaîne JSON
        if (is_string($data) && json_decode($data) !== null) {
            $data = json_decode($data, true);
        }

        // Convertir seulement si c'est un tableau
        if (is_array($data)) {
            $convertedData = $this->convertSnakeToCamel($data);
            return json_encode($convertedData, $this->config->jsonOptions ?? JSON_UNESCAPED_UNICODE);
        }

        // Retourner tel quel si ce n'est pas un tableau
        return parent::format($data);
    }

    private function convertSnakeToCamel($data)
    {
        if (!is_array($data)) {
            return $data;
        }

        $result = [];
        foreach ($data as $key => $value) {
            // Éviter la récursion sur les objets ou ressources
            if (is_object($value) || is_resource($value)) {
                $result[$key] = $value;
                continue;
            }

            $camelKey = lcfirst(str_replace('_', '', ucwords($key, '_')));

            if (is_array($value)) {
                $result[$camelKey] = $this->convertSnakeToCamel($value);
            } else {
                $result[$camelKey] = $value;
            }
        }

        return $result;
    }
}