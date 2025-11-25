<?php

namespace eseperio\virtualfields\models;

use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;

/**
 * VirtualFieldValue ActiveRecord model
 * 
 * This model represents a value for a virtual field.
 * 
 * @property int $id
 * @property int $definition_id Reference to virtual_field_definition
 * @property int $entity_type Integer identifier for the entity type
 * @property int $entity_id ID of the entity instance
 * @property string $value The actual field value (stored as text, cast according to data_type)
 * @property int $created_at
 * @property int $updated_at
 * 
 * @property VirtualFieldDefinition $definition
 */
class VirtualFieldValue extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%virtual_field_value}}';
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
            [['definition_id', 'entity_type', 'entity_id'], 'required'],
            [['definition_id', 'entity_type', 'entity_id'], 'integer'],
            [['value'], 'string'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'definition_id' => 'Definition ID',
            'entity_type' => 'Entity Type',
            'entity_id' => 'Entity ID',
            'value' => 'Value',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }

    /**
     * Gets query for [[VirtualFieldDefinition]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getDefinition()
    {
        return $this->hasOne(VirtualFieldDefinition::class, ['id' => 'definition_id']);
    }
}
