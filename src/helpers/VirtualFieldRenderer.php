<?php

namespace eseperio\virtualfields\helpers;

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use eseperio\virtualfields\models\VirtualFieldDefinition;

/**
 * VirtualFieldRenderer
 * 
 * Helper class for rendering virtual fields in forms.
 */
class VirtualFieldRenderer
{
    /**
     * Render a form input for a virtual field
     * 
     * @param ActiveForm $form
     * @param \yii\db\ActiveRecord $model
     * @param VirtualFieldDefinition $definition
     * @param array $options
     * @return string
     */
    public static function renderField($form, $model, $definition, $options = [])
    {
        $fieldName = $definition->name;
        $fieldOptions = $definition->getOptionsArray();
        
        // Merge options
        $options = array_merge($fieldOptions, $options);
        
        // Set label
        if (!isset($options['label']) && !empty($definition->label)) {
            $options['label'] = $definition->label;
        }

        switch ($definition->data_type) {
            case 'bool':
                return $form->field($model, $fieldName)->checkbox($options);
                
            case 'text':
                return $form->field($model, $fieldName)->textarea($options);
                
            case 'date':
                $options['type'] = 'date';
                return $form->field($model, $fieldName)->input('date', $options);
                
            case 'datetime':
                $options['type'] = 'datetime-local';
                return $form->field($model, $fieldName)->input('datetime-local', $options);
                
            case 'int':
                $options['type'] = 'number';
                return $form->field($model, $fieldName)->input('number', $options);
                
            case 'float':
                $options['type'] = 'number';
                $options['step'] = 'any';
                return $form->field($model, $fieldName)->input('number', $options);
                
            case 'json':
                return $form->field($model, $fieldName)->textarea(array_merge([
                    'rows' => 6,
                    'placeholder' => 'Enter valid JSON',
                ], $options));
                
            default: // string
                return $form->field($model, $fieldName)->textInput($options);
        }
    }

    /**
     * Render all virtual fields for a model
     * 
     * @param ActiveForm $form
     * @param \yii\db\ActiveRecord $model
     * @param VirtualFieldDefinition[] $definitions
     * @param array $options
     * @return string
     */
    public static function renderFields($form, $model, $definitions, $options = [])
    {
        $html = '';
        
        foreach ($definitions as $definition) {
            $html .= static::renderField($form, $model, $definition, $options);
        }
        
        return $html;
    }
}
