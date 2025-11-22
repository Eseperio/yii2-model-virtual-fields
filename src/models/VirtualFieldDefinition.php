<?php

namespace eseperio\virtualfields\models;

use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;
use eseperio\virtualfields\validators\FieldNameValidator;

/**
 * VirtualFieldDefinition ActiveRecord model
 * 
 * This model represents a virtual field definition.
 * 
 * @property int $id
 * @property int $entity_type Integer identifier for the entity type
 * @property string $name Field name (must be unique per entity type)
 * @property string $label Human-readable label
 * @property string $data_type Data type: string, int, float, bool, date, datetime, json, etc.
 * @property bool $required Whether the field is required
 * @property bool $multiple Whether the field can have multiple values
 * @property string $options JSON-encoded options/configuration for the field
 * @property string $default_value Default value for the field
 * @property bool $active Whether the field is active
 * @property int $created_at
 * @property int $updated_at
 * 
 * @property VirtualFieldValue[] $values
 */
class VirtualFieldDefinition extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%virtual_field_definition}}';
    }

    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            TimestampBehavior::class,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['entity_type', 'name', 'data_type'], 'required'],
            [['entity_type'], 'integer'],
            [['required', 'multiple', 'active'], 'boolean'],
            [['options', 'default_value'], 'string'],
            [['name'], 'string', 'max' => 64],
            [['label'], 'string', 'max' => 255],
            [['data_type'], 'string', 'max' => 32],
            [['name'], 'match', 'pattern' => '/^[a-zA-Z_][a-zA-Z0-9_]*$/', 'message' => 'Field name must start with a letter or underscore and contain only alphanumeric characters and underscores.'],
            [['name'], FieldNameValidator::class],
            [['entity_type', 'name'], 'unique', 'targetAttribute' => ['entity_type', 'name'], 'message' => 'This field name is already defined for this entity type.'],
            [['data_type'], 'in', 'range' => ['string', 'int', 'float', 'bool', 'date', 'datetime', 'json', 'text']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'entity_type' => 'Entity Type',
            'name' => 'Field Name',
            'label' => 'Label',
            'data_type' => 'Data Type',
            'required' => 'Required',
            'multiple' => 'Multiple',
            'options' => 'Options',
            'default_value' => 'Default Value',
            'active' => 'Active',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }

    /**
     * Gets query for [[VirtualFieldValue]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getValues()
    {
        return $this->hasMany(VirtualFieldValue::class, ['definition_id' => 'id']);
    }

    /**
     * Get options as array
     * 
     * @return array
     */
    public function getOptionsArray()
    {
        if (empty($this->options)) {
            return [];
        }
        
        $decoded = json_decode($this->options, true);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Set options from array
     * 
     * @param array $options
     */
    public function setOptionsArray($options)
    {
        $this->options = json_encode($options);
    }
}
