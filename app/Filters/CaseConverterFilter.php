<?php

namespace App\Filters;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Filters\FilterInterface;

class CaseConverterFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        // Conversion entrante camelCase → snake_case
        if (in_array($request->getMethod(), ['POST', 'PUT', 'PATCH'])) {
            $data = $this->getRequestData($request);

            if (!empty($data) && is_array($data)) {
                $convertedData = $this->convertKeys($data, 'camelToSnake');
                $this->setRequestData($request, $convertedData);
            }
        }

        return $request;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Conversion sortante snake_case → camelCase
        if ($response->getHeaderLine('Content-Type') === 'application/json') {
            $body = $response->getBody();

            if (!empty($body)) {
                $data = json_decode($body, true);

                if (json_last_error() === JSON_ERROR_NONE && is_array($data)) {
                    $convertedData = $this->convertKeys($data, 'snakeToCamel');
                    $response->setBody(json_encode($convertedData));
                }
            }
        }

        return $response;
    }

    private function getRequestData($request)
    {
        if ($request->getMethod() === 'GET') {
            return $request->getGet();
        }

        $contentType = $request->getHeaderLine('Content-Type');

        if (strpos($contentType, 'application/json') !== false) {
            return $request->getJSON(true);
        }

        return $request->getPost();
    }

    private function setRequestData($request, $data)
    {
        $contentType = $request->getHeaderLine('Content-Type');

        if (strpos($contentType, 'application/json') !== false) {
            $request->setBody(json_encode($data));
        } else {
            $request->setGlobal('post', $data);
        }
    }

    private function convertKeys(array $data, string $direction): array
    {
        $result = [];

        foreach ($data as $key => $value) {
            $convertedKey = ($direction === 'camelToSnake')
                ? $this->camelToSnake($key)
                : $this->snakeToCamel($key);

            if (is_array($value)) {
                $result[$convertedKey] = $this->convertKeys($value, $direction);
            } else {
                $result[$convertedKey] = $value;
            }
        }

        return $result;
    }

    private function camelToSnake(string $input): string
    {
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $input));
    }

    private function snakeToCamel(string $input): string
    {
        return lcfirst(str_replace('_', '', ucwords($input, '_')));
    }
}