<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Utility class for converting array keys between camelCase and snake_case.
 * 
 * Used to maintain strict camelCase API contract while preserving snake_case DB columns.
 */
class CaseConverter
{
    /**
     * Convert array keys from camelCase to snake_case (for DB persistence).
     * 
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public static function toSnakeCase(array $data): array
    {
        $result = [];

        foreach ($data as $key => $value) {
            $snakeKey = Str::snake($key);
            
            if (is_array($value) && !self::isIndexedArray($value)) {
                $result[$snakeKey] = self::toSnakeCase($value);
            } elseif (is_array($value) && self::isIndexedArray($value)) {
                $result[$snakeKey] = array_map(
                    fn($item) => is_array($item) ? self::toSnakeCase($item) : $item,
                    $value
                );
            } else {
                $result[$snakeKey] = $value;
            }
        }

        return $result;
    }

    /**
     * Convert array keys from snake_case to camelCase (for API responses).
     * 
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public static function toCamelCase(array $data): array
    {
        $result = [];

        foreach ($data as $key => $value) {
            $camelKey = Str::camel($key);
            
            if (is_array($value) && !self::isIndexedArray($value)) {
                $result[$camelKey] = self::toCamelCase($value);
            } elseif (is_array($value) && self::isIndexedArray($value)) {
                $result[$camelKey] = array_map(
                    fn($item) => is_array($item) ? self::toCamelCase($item) : $item,
                    $value
                );
            } else {
                $result[$camelKey] = $value;
            }
        }

        return $result;
    }

    /**
     * Convert specific keys from camelCase to snake_case.
     * Useful when you only need to map certain fields.
     * 
     * @param array<string, mixed> $data
     * @param array<string> $keys Keys to convert (in camelCase)
     * @return array<string, mixed>
     */
    public static function keysToSnakeCase(array $data, array $keys): array
    {
        $result = [];

        foreach ($data as $key => $value) {
            if (in_array($key, $keys, true)) {
                $result[Str::snake($key)] = $value;
            } else {
                $result[$key] = $value;
            }
        }

        return $result;
    }

    /**
     * Check if array is indexed (sequential numeric keys starting from 0).
     */
    private static function isIndexedArray(array $array): bool
    {
        if (empty($array)) {
            return true;
        }

        return array_keys($array) === range(0, count($array) - 1);
    }
}
