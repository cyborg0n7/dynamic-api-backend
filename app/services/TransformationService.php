<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class TransformationService
{
    /**
     * Transform API request before execution
     */
    public function transformRequest(array $api, array $previousResults = []): array
    {
        $transformedApi = $api;

        // Apply request transformations if specified
        if (isset($api['request_transformations']) && is_array($api['request_transformations'])) {
            foreach ($api['request_transformations'] as $transformation) {
                $transformedApi = $this->applyTransformation($transformedApi, $transformation, $previousResults);
            }
        }

        return $transformedApi;
    }

    /**
     * Transform API response after execution
     */
    public function transformResponse(array $result, array $api): array
    {
        $transformedResult = $result;

        // Apply response transformations if specified
        if (isset($api['response_transformations']) && is_array($api['response_transformations'])) {
            foreach ($api['response_transformations'] as $transformation) {
                $transformedResult = $this->applyResponseTransformation($transformedResult, $transformation);
            }
        }

        return $transformedResult;
    }

    /**
     * Apply a single transformation to the API request
     */
    private function applyTransformation(array $api, array $transformation, array $previousResults): array
    {
        $type = $transformation['type'] ?? '';
        
        switch ($type) {
            case 'add_header':
                $api['headers'] = $api['headers'] ?? [];
                $api['headers'][$transformation['key']] = $this->resolveValue($transformation['value'], $previousResults);
                break;
                
            case 'modify_url':
                $api['url'] = $this->resolveValue($transformation['value'], $previousResults);
                break;
                
            case 'add_query_param':
                $separator = strpos($api['url'], '?') !== false ? '&' : '?';
                $api['url'] .= $separator . $transformation['key'] . '=' . urlencode($this->resolveValue($transformation['value'], $previousResults));
                break;
                
            case 'modify_body':
                $api['body'] = $api['body'] ?? [];
                $api['body'][$transformation['key']] = $this->resolveValue($transformation['value'], $previousResults);
                break;
                
            case 'set_timeout':
                $api['timeout'] = (int) $this->resolveValue($transformation['value'], $previousResults);
                break;
                
            default:
                Log::warning("Unknown transformation type: {$type}");
        }

        return $api;
    }

    /**
     * Apply a single transformation to the API response
     */
    private function applyResponseTransformation(array $result, array $transformation): array
    {
        $type = $transformation['type'] ?? '';
        
        switch ($type) {
            case 'extract_field':
                if (isset($result['data']) && is_array($result['data'])) {
                    $fieldPath = $transformation['field_path'] ?? '';
                    $extractedValue = $this->extractFieldFromData($result['data'], $fieldPath);
                    $result['extracted_data'][$transformation['alias'] ?? $fieldPath] = $extractedValue;
                }
                break;
                
            case 'filter_data':
                if (isset($result['data']) && is_array($result['data'])) {
                    $result['data'] = $this->filterData($result['data'], $transformation['filter'] ?? []);
                }
                break;
                
            case 'rename_field':
                if (isset($result['data'][$transformation['old_name']])) {
                    $result['data'][$transformation['new_name']] = $result['data'][$transformation['old_name']];
                    unset($result['data'][$transformation['old_name']]);
                }
                break;
                
            default:
                Log::warning("Unknown response transformation type: {$type}");
        }

        return $result;
    }

    /**
     * Resolve dynamic values from previous results
     */
    private function resolveValue(string $value, array $previousResults): string
    {
        // Handle dynamic value resolution from previous API results
        if (preg_match('/\{\{(\d+)\.(.+)\}\}/', $value, $matches)) {
            $apiIndex = (int) $matches[1];
            $fieldPath = $matches[2];
            
            if (isset($previousResults[$apiIndex]['data'])) {
                $resolvedValue = $this->extractFieldFromData($previousResults[$apiIndex]['data'], $fieldPath);
                return $resolvedValue ?? $value;
            }
        }
        
        return $value;
    }

    /**
     * Extract field from nested data using dot notation
     */
    private function extractFieldFromData(array $data, string $fieldPath)
    {
        $keys = explode('.', $fieldPath);
        $current = $data;
        
        foreach ($keys as $key) {
            if (is_array($current) && isset($current[$key])) {
                $current = $current[$key];
            } else {
                return null;
            }
        }
        
        return $current;
    }

    /**
     * Filter data based on specified criteria
     */
    private function filterData(array $data, array $filter): array
    {
        if (empty($filter)) {
            return $data;
        }
        
        // Simple filtering - can be extended for more complex logic
        return array_filter($data, function ($item) use ($filter) {
            foreach ($filter as $key => $value) {
                if (!isset($item[$key]) || $item[$key] !== $value) {
                    return false;
                }
            }
            return true;
        });
    }
}
