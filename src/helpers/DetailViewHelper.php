<?php

namespace eseperio\virtualfields\helpers;

use eseperio\virtualfields\models\VirtualFieldDefinition;

/**
 * DetailViewHelper
 * 
 * Helper class for integrating virtual fields with DetailView.
 */
class DetailViewHelper
{
    /**
     * Convert virtual field definitions into DetailView-compatible attribute arrays
     * 
     * @param VirtualFieldDefinition[] $definitions
     * @param array $options
     * @return array
     */
    public static function getAttributes($definitions, $options = [])
    {
        $attributes = [];
        
        foreach ($definitions as $definition) {
            $attribute = [
                'attribute' => $definition->name,
                'label' => $definition->label ?: $definition->name,
            ];
            
            // Add format based on data type
            switch ($definition->data_type) {
                case 'bool':
                    $attribute['format'] = 'boolean';
                    break;
                case 'date':
                    $attribute['format'] = ['date', 'php:Y-m-d'];
                    break;
                case 'datetime':
                    $attribute['format'] = ['datetime', 'php:Y-m-d H:i:s'];
                    break;
                case 'float':
                    $attribute['format'] = ['decimal', 2];
                    break;
                case 'json':
                    $attribute['value'] = function ($model) use ($definition) {
                        $value = $model->{$definition->name};
                        return is_array($value) ? json_encode($value, JSON_PRETTY_PRINT) : $value;
                    };
                    $attribute['format'] = 'raw';
                    break;
                case 'text':
                    $attribute['format'] = 'ntext';
                    break;
                default:
                    $attribute['format'] = 'text';
            }
            
            // Merge with custom options if provided
            if (isset($options[$definition->name])) {
                $attribute = array_merge($attribute, $options[$definition->name]);
            }
            
            $attributes[] = $attribute;
        }
        
        return $attributes;
    }

    /**
     * Get a single attribute configuration
     * 
     * @param VirtualFieldDefinition $definition
     * @param array $options
     * @return array
     */
    public static function getAttribute($definition, $options = [])
    {
        return static::getAttributes([$definition], $options)[0] ?? [];
    }
}
