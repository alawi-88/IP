<?php

namespace App\Traits;

trait SafeDataFormatting
{
    /**
     * Safely convert any data type to string for display
     */
    protected function safeConvertToString($data): string
    {
        if (is_null($data)) {
            return 'N/A';
        }
        
        if (is_string($data)) {
            return $data;
        }
        
        if (is_numeric($data)) {
            return (string) $data;
        }
        
        if (is_bool($data)) {
            return $data ? 'true' : 'false';
        }
        
        // Handle Carbon/DateTime objects
        if (is_object($data)) {
            if ($data instanceof \DateTime || $data instanceof \Carbon\Carbon) {
                return $data->format('M d, Y H:i');
            }
            if (method_exists($data, 'toArray')) {
                return $this->convertArrayToString($data->toArray());
            }
            if (method_exists($data, '__toString')) {
                return (string) $data;
            }
            return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        }
        
        if (is_array($data)) {
            // Check if it's a Carbon/DateTime array representation
            if (isset($data['formatted']) && isset($data['year']) && isset($data['month'])) {
                return $data['formatted'];
            }
            return $this->convertArrayToString($data);
        }
        
        return (string) $data;
    }
    
    /**
     * Convert array to readable string
     */
    private function convertArrayToString(array $data): string
    {
        if (empty($data)) {
            return 'Empty array / مصفوفة فارغة';
        }
        
        // If it's a simple array with string/numeric values
        if ($this->isSimpleArray($data)) {
            return implode(', ', array_map(function($value) {
                if (is_string($value)) {
                    return $value;
                }
                if (is_numeric($value)) {
                    return (string) $value;
                }
                if (is_bool($value)) {
                    return $value ? 'true' : 'false';
                }
                if (is_null($value)) {
                    return 'null';
                }
                return (string) $value;
            }, $data));
        }
        
        // For complex arrays, return JSON
        return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }
    
    /**
     * Check if array contains only simple values
     */
    private function isSimpleArray(array $data): bool
    {
        foreach ($data as $value) {
            if (!is_string($value) && !is_numeric($value) && !is_bool($value) && !is_null($value)) {
                return false;
            }
        }
        return true;
    }
    
    /**
     * Safe format state using with comprehensive error handling
     */
    protected function safeFormatState(callable $formatter, $state, string $fieldName = 'unknown'): string
    {
        try {
            $result = $formatter($state);
            
            // Ensure result is always a string
            if (is_array($result)) {
                return $this->convertArrayToString($result);
            }
            
            if (is_object($result)) {
                if (method_exists($result, 'toArray')) {
                    return $this->convertArrayToString($result->toArray());
                }
                if (method_exists($result, '__toString')) {
                    return (string) $result;
                }
                return json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            }
            
            return (string) $result;
        } catch (\Exception $e) {
            \Log::error("Error formatting {$fieldName}: " . $e->getMessage(), [
                'state' => $state,
                'state_type' => gettype($state),
                'exception' => $e->getTraceAsString()
            ]);
            return "Error displaying {$fieldName} / خطأ في عرض {$fieldName}";
        }
    }
}