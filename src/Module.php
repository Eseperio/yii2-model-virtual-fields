<?php

namespace eseperio\virtualfields;

use Yii;
use yii\base\InvalidConfigException;
use yii\db\ActiveRecord;

/**
 * VirtualFields Module
 * 
 * This module manages virtual (dynamic) fields for ActiveRecord models.
 * 
 * Configuration example:
 * ```php
 * 'modules' => [
 *     'virtualFields' => [
 *         'class' => 'eseperio\virtualfields\Module',
 *         'entityMap' => [
 *             1 => 'app\models\User',
 *             2 => 'app\models\Product',
 *             // ... more entity type mappings
 *         ],
 *     ],
 * ],
 * ```
 */
class Module extends \yii\base\Module
{
    /**
     * @var array Map of entity type IDs (integers) to fully qualified class names of ActiveRecord models.
     * Example: [1 => 'app\models\User', 2 => 'app\models\Product']
     */
    public $entityMap = [];

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        
        $this->validateEntityMap();
        
        // Register module components
        $this->setComponents([
            'service' => [
                'class' => 'eseperio\virtualfields\services\VirtualFieldService',
            ],
        ]);
    }

    /**
     * Validates the entityMap configuration
     * 
     * @throws InvalidConfigException if entityMap is invalid
     */
    protected function validateEntityMap()
    {
        if (!is_array($this->entityMap)) {
            throw new InvalidConfigException('The "entityMap" property must be an array.');
        }

        foreach ($this->entityMap as $key => $value) {
            // Validate that keys are integers
            if (!is_int($key)) {
                throw new InvalidConfigException(
                    "All keys in entityMap must be integers. Invalid key: " . var_export($key, true)
                );
            }

            // Validate that values are strings (class names)
            if (!is_string($value)) {
                throw new InvalidConfigException(
                    "All values in entityMap must be class names (strings). Invalid value for key {$key}: " 
                    . var_export($value, true)
                );
            }

            // Validate that the class exists
            if (!class_exists($value)) {
                throw new InvalidConfigException(
                    "Class '{$value}' specified in entityMap for entity type {$key} does not exist."
                );
            }

            // Validate that the class extends ActiveRecord
            if (!is_subclass_of($value, ActiveRecord::class)) {
                throw new InvalidConfigException(
                    "Class '{$value}' specified in entityMap for entity type {$key} must extend " 
                    . ActiveRecord::class
                );
            }
        }
    }

    /**
     * Get the fully qualified class name for an entity type
     * 
     * @param int $entityType
     * @return string|null
     */
    public function getEntityClass($entityType)
    {
        return $this->entityMap[$entityType] ?? null;
    }

    /**
     * Get the entity type ID for a class name
     * 
     * @param string $className
     * @return int|null
     */
    public function getEntityType($className)
    {
        $flipped = array_flip($this->entityMap);
        return $flipped[$className] ?? null;
    }

    /**
     * Get all registered entity types
     * 
     * @return array
     */
    public function getEntityTypes()
    {
        return array_keys($this->entityMap);
    }
}
