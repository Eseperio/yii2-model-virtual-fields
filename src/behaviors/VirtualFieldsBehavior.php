<?php

namespace eseperio\virtualfields\behaviors;

use Yii;
use yii\base\Behavior;
use yii\base\InvalidConfigException;
use yii\base\ModelEvent;
use yii\db\ActiveRecord;
use yii\validators\Validator;
use eseperio\virtualfields\Module;
use eseperio\virtualfields\services\VirtualFieldService;
use eseperio\virtualfields\models\VirtualFieldDefinition;

/**
 * VirtualFieldsBehavior
 * 
 * This behavior adds virtual field support to ActiveRecord models.
 * 
 * Usage:
 * ```php
 * public function behaviors()
 * {
 *     return [
 *         'virtualFields' => [
 *             'class' => VirtualFieldsBehavior::class,
 *         ],
 *     ];
 * }
 * 
 * // The model must implement getObjectType() to return entity type ID
 * public function getObjectType()
 * {
 *     return 1; // or get from configuration
 * }
 * ```
 */
class VirtualFieldsBehavior extends Behavior
{
    /**
     * @var array Cached virtual field definitions
     */
    private $_definitions;

    /**
     * @var array Cached virtual field values
     */
    private $_values;

    /**
     * @var array Modified virtual field values (to be saved)
     */
    private $_modifiedValues = [];

    /**
     * @var VirtualFieldService
     */
    private $_service;

    /**
     * {@inheritdoc}
     */
    public function events()
    {
        return [
            ActiveRecord::EVENT_AFTER_FIND => 'afterFind',
            ActiveRecord::EVENT_BEFORE_VALIDATE => 'beforeValidate',
            ActiveRecord::EVENT_AFTER_INSERT => 'afterInsert',
            ActiveRecord::EVENT_AFTER_UPDATE => 'afterUpdate',
            ActiveRecord::EVENT_AFTER_DELETE => 'afterDelete',
        ];
    }

    /**
     * Get the VirtualFieldService instance
     * 
     * @return VirtualFieldService
     * @throws InvalidConfigException
     */
    protected function getService()
    {
        if ($this->_service === null) {
            $module = $this->getModule();
            $this->_service = $module->get('service');
        }
        return $this->_service;
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

    /**
     * Get entity type for the owner model
     * 
     * @return int
     * @throws InvalidConfigException
     */
    protected function getEntityType()
    {
        /** @var ActiveRecord $owner */
        $owner = $this->owner;
        
        if (!method_exists($owner, 'getObjectType')) {
            throw new InvalidConfigException(
                get_class($owner) . ' must implement getObjectType() method to return entity type ID.'
            );
        }

        $entityType = $owner->getObjectType();
        
        if (!is_int($entityType)) {
            throw new InvalidConfigException('getObjectType() must return an integer.');
        }

        return $entityType;
    }

    /**
     * Get field definitions
     * 
     * @return VirtualFieldDefinition[]
     */
    protected function getDefinitions()
    {
        if ($this->_definitions === null) {
            $this->_definitions = $this->getService()->getDefinitions($this->getEntityType());
        }
        return $this->_definitions;
    }

    /**
     * Get field values
     * 
     * @return array
     */
    protected function getValues()
    {
        if ($this->_values === null) {
            /** @var ActiveRecord $owner */
            $owner = $this->owner;
            
            if ($owner->getIsNewRecord() || !$owner->getPrimaryKey()) {
                $this->_values = [];
            } else {
                $this->_values = $this->getService()->getValues(
                    $this->getEntityType(),
                    $owner->getPrimaryKey()
                );
            }
        }
        return $this->_values;
    }

    /**
     * After find event handler
     * 
     * @param \yii\base\Event $event
     */
    public function afterFind($event)
    {
        // Load virtual field values
        $this->getValues();
    }

    /**
     * Before validate event handler
     * 
     * @param ModelEvent $event
     */
    public function beforeValidate($event)
    {
        /** @var ActiveRecord $owner */
        $owner = $this->owner;
        
        // Add validation rules for virtual fields
        $definitions = $this->getDefinitions();
        
        foreach ($definitions as $definition) {
            // Required validation
            if ($definition->required) {
                $validator = Validator::createValidator('required', $owner, [$definition->name]);
                $owner->getValidators()->append($validator);
            }

            // Type validation
            switch ($definition->data_type) {
                case 'int':
                    $validator = Validator::createValidator('integer', $owner, [$definition->name]);
                    $owner->getValidators()->append($validator);
                    break;
                case 'float':
                    $validator = Validator::createValidator('number', $owner, [$definition->name]);
                    $owner->getValidators()->append($validator);
                    break;
                case 'bool':
                    $validator = Validator::createValidator('boolean', $owner, [$definition->name]);
                    $owner->getValidators()->append($validator);
                    break;
                case 'date':
                    $validator = Validator::createValidator('date', $owner, [$definition->name], [
                        'format' => 'php:Y-m-d'
                    ]);
                    $owner->getValidators()->append($validator);
                    break;
                case 'datetime':
                    $validator = Validator::createValidator('date', $owner, [$definition->name], [
                        'format' => 'php:Y-m-d H:i:s'
                    ]);
                    $owner->getValidators()->append($validator);
                    break;
            }
        }
    }

    /**
     * After insert event handler
     * 
     * @param \yii\base\Event $event
     */
    public function afterInsert($event)
    {
        $this->saveVirtualFields();
    }

    /**
     * After update event handler
     * 
     * @param \yii\base\Event $event
     */
    public function afterUpdate($event)
    {
        $this->saveVirtualFields();
    }

    /**
     * After delete event handler
     * 
     * @param \yii\base\Event $event
     */
    public function afterDelete($event)
    {
        /** @var ActiveRecord $owner */
        $owner = $this->owner;
        
        $this->getService()->deleteValues(
            $this->getEntityType(),
            $owner->getPrimaryKey()
        );
    }

    /**
     * Save virtual field values
     */
    protected function saveVirtualFields()
    {
        if (empty($this->_modifiedValues)) {
            return;
        }

        /** @var ActiveRecord $owner */
        $owner = $this->owner;

        $this->getService()->setValues(
            $this->getEntityType(),
            $owner->getPrimaryKey(),
            $this->_modifiedValues
        );

        // Clear modified values after saving
        $this->_modifiedValues = [];
    }

    /**
     * Check if a property can be retrieved via "get"
     * 
     * @param string $name
     * @param bool $checkVars
     * @return bool
     */
    public function canGetProperty($name, $checkVars = true)
    {
        $definitions = $this->getDefinitions();
        
        foreach ($definitions as $definition) {
            if ($definition->name === $name) {
                return true;
            }
        }

        return parent::canGetProperty($name, $checkVars);
    }

    /**
     * Check if a property can be set via "set"
     * 
     * @param string $name
     * @param bool $checkVars
     * @return bool
     */
    public function canSetProperty($name, $checkVars = true)
    {
        $definitions = $this->getDefinitions();
        
        foreach ($definitions as $definition) {
            if ($definition->name === $name) {
                return true;
            }
        }

        return parent::canSetProperty($name, $checkVars);
    }

    /**
     * Get a virtual field value
     * 
     * @param string $name
     * @return mixed
     */
    public function __get($name)
    {
        $definitions = $this->getDefinitions();
        
        foreach ($definitions as $definition) {
            if ($definition->name === $name) {
                // Check modified values first
                if (array_key_exists($name, $this->_modifiedValues)) {
                    return $this->_modifiedValues[$name];
                }

                $values = $this->getValues();
                return $values[$name] ?? null;
            }
        }

        return parent::__get($name);
    }

    /**
     * Set a virtual field value
     * 
     * @param string $name
     * @param mixed $value
     */
    public function __set($name, $value)
    {
        $definitions = $this->getDefinitions();
        
        foreach ($definitions as $definition) {
            if ($definition->name === $name) {
                $this->_modifiedValues[$name] = $value;
                return;
            }
        }

        parent::__set($name, $value);
    }

    /**
     * Get all virtual field names
     * 
     * @return array
     */
    public function getVirtualFieldNames()
    {
        $definitions = $this->getDefinitions();
        return array_map(function ($def) {
            return $def->name;
        }, $definitions);
    }

    /**
     * Get all virtual field values as array
     * 
     * @return array
     */
    public function getVirtualFieldValues()
    {
        $values = $this->getValues();
        
        // Merge with modified values
        return array_merge($values, $this->_modifiedValues);
    }
}
