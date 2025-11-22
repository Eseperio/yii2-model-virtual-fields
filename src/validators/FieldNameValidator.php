<?php

namespace eseperio\virtualfields\validators;

use Yii;
use yii\base\InvalidConfigException;
use yii\validators\Validator;
use yii\db\ActiveRecord;
use eseperio\virtualfields\Module;

/**
 * FieldNameValidator
 * 
 * Validates that a virtual field name doesn't conflict with existing model properties.
 * 
 * Usage in VirtualFieldDefinition model:
 * ```php
 * public function rules()
 * {
 *     return [
 *         ['name', FieldNameValidator::class],
 *     ];
 * }
 * ```
 */
class FieldNameValidator extends Validator
{
    /**
     * @var array Reserved field names that cannot be used
     */
    public $reservedNames = [
        'id',
        'attributes',
        'errors',
        'scenario',
        'validators',
        'behaviors',
        'isNewRecord',
        'oldAttributes',
    ];

    /**
     * {@inheritdoc}
     */
    public function validateAttribute($model, $attribute)
    {
        $fieldName = $model->$attribute;
        
        if (empty($fieldName)) {
            return;
        }

        // Check if field name is reserved
        if (in_array($fieldName, $this->reservedNames, true)) {
            $this->addError($model, $attribute, 'The field name "{value}" is reserved and cannot be used.', [
                'value' => $fieldName,
            ]);
            return;
        }

        // Get the entity type
        if (!isset($model->entity_type)) {
            return; // Can't validate without entity type
        }

        $entityType = $model->entity_type;

        // Get the entity class from module configuration
        try {
            $module = $this->getModule();
            $entityClass = $module->getEntityClass($entityType);
            
            if (!$entityClass) {
                return; // Can't validate if entity type is not configured
            }

            // Check against the actual model class
            $this->validateAgainstModel($model, $attribute, $fieldName, $entityClass);
            
        } catch (\Exception $e) {
            // If module is not configured, skip validation
            return;
        }
    }

    /**
     * Validate field name against model class
     * 
     * @param \yii\base\Model $model
     * @param string $attribute
     * @param string $fieldName
     * @param string $entityClass
     */
    protected function validateAgainstModel($model, $attribute, $fieldName, $entityClass)
    {
        if (!class_exists($entityClass)) {
            return;
        }

        // Create a temporary instance to check properties
        try {
            $reflection = new \ReflectionClass($entityClass);
            
            // Check for existing properties (public and protected)
            if ($reflection->hasProperty($fieldName)) {
                $property = $reflection->getProperty($fieldName);
                if ($property->isPublic() || $property->isProtected()) {
                    $this->addError($model, $attribute, 
                        'The field name "{value}" conflicts with an existing property in {class}.', [
                        'value' => $fieldName,
                        'class' => $entityClass,
                    ]);
                    return;
                }
            }

            // Check for getter method
            $getterMethod = 'get' . ucfirst($fieldName);
            if ($reflection->hasMethod($getterMethod)) {
                $method = $reflection->getMethod($getterMethod);
                if ($method->isPublic()) {
                    $this->addError($model, $attribute, 
                        'The field name "{value}" conflicts with getter method {method}() in {class}.', [
                        'value' => $fieldName,
                        'method' => $getterMethod,
                        'class' => $entityClass,
                    ]);
                    return;
                }
            }

            // Check for setter method
            $setterMethod = 'set' . ucfirst($fieldName);
            if ($reflection->hasMethod($setterMethod)) {
                $method = $reflection->getMethod($setterMethod);
                if ($method->isPublic()) {
                    $this->addError($model, $attribute, 
                        'The field name "{value}" conflicts with setter method {method}() in {class}.', [
                        'value' => $fieldName,
                        'method' => $setterMethod,
                        'class' => $entityClass,
                    ]);
                    return;
                }
            }

            // Check against database columns if it's an ActiveRecord
            if (is_subclass_of($entityClass, ActiveRecord::class)) {
                $tempInstance = new $entityClass();
                $tableSchema = $tempInstance->getTableSchema();
                
                if ($tableSchema && isset($tableSchema->columns[$fieldName])) {
                    $this->addError($model, $attribute, 
                        'The field name "{value}" conflicts with a database column in {class}.', [
                        'value' => $fieldName,
                        'class' => $entityClass,
                    ]);
                    return;
                }
            }
            
        } catch (\Exception $e) {
            // If we can't check, skip validation
            Yii::warning("Could not validate field name '{$fieldName}' against class '{$entityClass}': " . $e->getMessage());
        }
    }

    /**
     * Get the VirtualFields module
     * 
     * @return Module
     * @throws InvalidConfigException
     */
    protected function getModule()
    {
        if (!Yii::$app->hasModule('virtualFields')) {
            throw new InvalidConfigException('The virtualFields module is not configured.');
        }
        return Yii::$app->getModule('virtualFields');
    }
}
