<?php

use yii\db\Migration;

/**
 * Creates table for virtual field values
 */
class m000000_000001_create_virtual_field_value_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%virtual_field_value}}', [
            'id' => $this->primaryKey(),
            'definition_id' => $this->integer()->notNull()->comment('Reference to virtual_field_definition'),
            'entity_type' => $this->integer()->notNull()->comment('Integer identifier for the entity type'),
            'entity_id' => $this->integer()->notNull()->comment('ID of the entity instance'),
            'value' => $this->text()->comment('The actual field value (stored as text, cast according to data_type)'),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ]);

        // Create foreign key to virtual_field_definition
        $this->addForeignKey(
            'fk-virtual_field_value-definition_id',
            '{{%virtual_field_value}}',
            'definition_id',
            '{{%virtual_field_definition}}',
            'id',
            'CASCADE'
        );

        // Create unique index for entity_type, entity_id, and definition_id combination
        // This ensures one value per field per entity instance
        $this->createIndex(
            'idx-virtual_field_value-unique',
            '{{%virtual_field_value}}',
            ['entity_type', 'entity_id', 'definition_id'],
            true
        );

        // Create index on entity_type and entity_id for quick lookups
        $this->createIndex(
            'idx-virtual_field_value-entity',
            '{{%virtual_field_value}}',
            ['entity_type', 'entity_id']
        );

        // Create index on definition_id for reverse lookups
        $this->createIndex(
            'idx-virtual_field_value-definition_id',
            '{{%virtual_field_value}}',
            'definition_id'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%virtual_field_value}}');
    }
}
