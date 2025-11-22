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
            'entity_type' => $this->integer()->notNull(),
            'name' => $this->string(64)->notNull(),
            'label' => $this->string(255),
            'data_type' => $this->string(32)->notNull(),
            'required' => $this->boolean()->defaultValue(false),
            'multiple' => $this->boolean()->defaultValue(false),
            'options' => $this->text(),
            'default_value' => $this->text(),
            'active' => $this->boolean()->defaultValue(true),
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
