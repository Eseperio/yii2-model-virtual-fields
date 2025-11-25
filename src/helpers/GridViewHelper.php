<?php

namespace eseperio\virtualfields\helpers;

use yii\grid\DataColumn;
use eseperio\virtualfields\models\VirtualFieldDefinition;

/**
 * GridViewHelper
 * 
 * Helper class for integrating virtual fields with GridView.
 */
class GridViewHelper
{
    /**
     * Convert virtual field definitions into GridView-compatible column arrays
     * 
     * @param VirtualFieldDefinition[] $definitions
     * @param array $options
     * @return array
     */
    public static function getColumns($definitions, $options = [])
    {
        $columns = [];
        
        foreach ($definitions as $definition) {
            $column = [
                'class' => DataColumn::class,
                'attribute' => $definition->name,
                'label' => $definition->label ?: $definition->name,
            ];
            
            // Add format and value based on data type
            switch ($definition->data_type) {
                case 'bool':
                    $column['format'] = 'boolean';
                    $column['filter'] = [0 => 'No', 1 => 'Yes'];
                    break;
                case 'date':
                    $column['format'] = ['date', 'php:Y-m-d'];
                    break;
                case 'datetime':
                    $column['format'] = ['datetime', 'php:Y-m-d H:i:s'];
                    break;
                case 'float':
                    $column['format'] = ['decimal', 2];
                    break;
                case 'json':
                    $column['value'] = function ($model) use ($definition) {
                        $value = $model->{$definition->name};
                        return is_array($value) ? json_encode($value) : $value;
                    };
                    $column['format'] = 'text';
                    break;
                case 'text':
                    $column['value'] = function ($model) use ($definition) {
                        $value = $model->{$definition->name};
                        return mb_substr($value, 0, 100) . (mb_strlen($value) > 100 ? '...' : '');
                    };
                    $column['format'] = 'text';
                    break;
                default:
                    $column['format'] = 'text';
            }
            
            // Merge with custom options if provided
            if (isset($options[$definition->name])) {
                $column = array_merge($column, $options[$definition->name]);
            }
            
            $columns[] = $column;
        }
        
        return $columns;
    }

    /**
     * Get a single column configuration
     * 
     * @param VirtualFieldDefinition $definition
     * @param array $options
     * @return array
     */
    public static function getColumn($definition, $options = [])
    {
        return static::getColumns([$definition], $options)[0] ?? [];
    }
}
