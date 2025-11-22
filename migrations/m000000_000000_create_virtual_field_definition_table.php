<?php

use yii\db\Migration;

/**
 * Creates table for virtual field definitions
 */
class m000000_000000_create_virtual_field_definition_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%virtual_field_definition}}', [
            'id' => $this->primaryKey(),
            'entity_type' => $this->integer()->notNull()->comment('Integer identifier for the entity type'),
            'name' => $this->string(64)->notNull()->comment('Field name (must be unique per entity type)'),
            'label' => $this->string(255)->comment('Human-readable label'),
            'data_type' => $this->string(32)->notNull()->comment('Data type: string, int, float, bool, date, datetime, json, etc.'),
            'required' => $this->boolean()->defaultValue(false)->comment('Whether the field is required'),
            'multiple' => $this->boolean()->defaultValue(false)->comment('Whether the field can have multiple values'),
            'options' => $this->text()->comment('JSON-encoded options/configuration for the field'),
            'default_value' => $this->text()->comment('Default value for the field'),
            'active' => $this->boolean()->defaultValue(true)->comment('Whether the field is active'),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ]);

        // Create unique index on entity_type and name combination
        $this->createIndex(
            'idx-virtual_field_definition-entity_type-name',
            '{{%virtual_field_definition}}',
            ['entity_type', 'name'],
            true
        );

        // Create index on entity_type for quick lookups
        $this->createIndex(
            'idx-virtual_field_definition-entity_type',
            '{{%virtual_field_definition}}',
            'entity_type'
        );

        // Create index on active for filtering
        $this->createIndex(
            'idx-virtual_field_definition-active',
            '{{%virtual_field_definition}}',
            'active'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%virtual_field_definition}}');
    }
}
