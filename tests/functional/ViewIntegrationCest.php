<?php

namespace tests\functional;

use Codeception\Test\Unit;
use tests\_app\models\TestModel;
use eseperio\virtualfields\models\VirtualFieldDefinition;
use eseperio\virtualfields\models\VirtualFieldValue;
use eseperio\virtualfields\helpers\VirtualFieldRenderer;
use eseperio\virtualfields\helpers\DetailViewHelper;
use eseperio\virtualfields\helpers\GridViewHelper;
use Yii;
use FunctionalTester;

class ViewIntegrationCest
{
    public function _before(FunctionalTester $I)
    {
        // Clean up any existing data
        VirtualFieldValue::deleteAll();
        VirtualFieldDefinition::deleteAll();
        TestModel::deleteAll();
    }

    public function testDetailViewHelper(FunctionalTester $I)
    {
        $I->wantTo('Test DetailView helper generates correct attributes');
        
        // Create field definitions
        $fields = [
            ['name' => 'phone_number', 'data_type' => 'string', 'label' => 'Phone'],
            ['name' => 'age', 'data_type' => 'int', 'label' => 'Age'],
            ['name' => 'active', 'data_type' => 'bool', 'label' => 'Active'],
        ];
        
        $definitions = [];
        foreach ($fields as $fieldData) {
            $field = new VirtualFieldDefinition([
                'entity_type' => 1,
                'name' => $fieldData['name'],
                'label' => $fieldData['label'],
                'data_type' => $fieldData['data_type'],
            ]);
            $field->save();
            $definitions[] = $field;
        }
        
        // Get attributes from helper
        $attributes = DetailViewHelper::getAttributes($definitions);
        
        $I->assertIsArray($attributes);
        $I->assertCount(3, $attributes);
        
        // Check phone_number attribute
        $I->assertEquals('phone_number', $attributes[0]['attribute']);
        $I->assertEquals('Phone', $attributes[0]['label']);
        $I->assertEquals('text', $attributes[0]['format']);
        
        // Check age attribute
        $I->assertEquals('age', $attributes[1]['attribute']);
        $I->assertEquals('Age', $attributes[1]['label']);
        
        // Check active attribute (boolean)
        $I->assertEquals('active', $attributes[2]['attribute']);
        $I->assertEquals('Active', $attributes[2]['label']);
        $I->assertEquals('boolean', $attributes[2]['format']);
    }

    public function testGridViewHelper(FunctionalTester $I)
    {
        $I->wantTo('Test GridView helper generates correct columns');
        
        // Create field definitions
        $fields = [
            ['name' => 'status', 'data_type' => 'string', 'label' => 'Status'],
            ['name' => 'score', 'data_type' => 'float', 'label' => 'Score'],
        ];
        
        $definitions = [];
        foreach ($fields as $fieldData) {
            $field = new VirtualFieldDefinition([
                'entity_type' => 1,
                'name' => $fieldData['name'],
                'label' => $fieldData['label'],
                'data_type' => $fieldData['data_type'],
            ]);
            $field->save();
            $definitions[] = $field;
        }
        
        // Get columns from helper
        $columns = GridViewHelper::getColumns($definitions);
        
        $I->assertIsArray($columns);
        $I->assertCount(2, $columns);
        
        // Check status column
        $I->assertEquals('status', $columns[0]['attribute']);
        $I->assertEquals('Status', $columns[0]['label']);
        $I->assertEquals('text', $columns[0]['format']);
        
        // Check score column (float)
        $I->assertEquals('score', $columns[1]['attribute']);
        $I->assertEquals('Score', $columns[1]['label']);
        $I->assertIsArray($columns[1]['format']);
        $I->assertEquals('decimal', $columns[1]['format'][0]);
    }

    public function testDetailViewWithModel(FunctionalTester $I)
    {
        $I->wantTo('Test DetailView integration with actual model data');
        
        // Create field definition
        $field = new VirtualFieldDefinition([
            'entity_type' => 1,
            'name' => 'bio',
            'label' => 'Biography',
            'data_type' => 'text',
        ]);
        $field->save();
        
        // Create model with data
        $model = new TestModel([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
        $model->bio = 'This is a test biography.';
        $model->save();
        
        // Get attributes
        $module = Yii::$app->getModule('virtualFields');
        $service = $module->get('service');
        $definitions = $service->getDefinitions(1);
        
        $attributes = DetailViewHelper::getAttributes($definitions);
        
        $I->assertIsArray($attributes);
        $I->assertGreaterThan(0, count($attributes));
        
        // Verify we can access the value
        $retrieved = TestModel::findOne($model->id);
        $I->assertEquals('This is a test biography.', $retrieved->bio);
    }

    public function testJsonFieldInDetailView(FunctionalTester $I)
    {
        $I->wantTo('Test JSON field formatting in DetailView');
        
        $field = new VirtualFieldDefinition([
            'entity_type' => 1,
            'name' => 'metadata',
            'label' => 'Metadata',
            'data_type' => 'json',
        ]);
        $field->save();
        
        $model = new TestModel([
            'name' => 'JSON User',
            'email' => 'json@example.com',
        ]);
        $model->metadata = ['key1' => 'value1', 'key2' => 'value2'];
        $model->save();
        
        $definitions = [$field];
        $attributes = DetailViewHelper::getAttributes($definitions);
        
        $I->assertEquals('metadata', $attributes[0]['attribute']);
        $I->assertEquals('raw', $attributes[0]['format']);
        $I->assertIsCallable($attributes[0]['value']);
        
        // Test the value callback
        $retrieved = TestModel::findOne($model->id);
        $formattedValue = call_user_func($attributes[0]['value'], $retrieved);
        $I->assertIsString($formattedValue);
        $I->assertStringContainsString('key1', $formattedValue);
        $I->assertStringContainsString('value1', $formattedValue);
    }

    public function testBooleanFieldInGridView(FunctionalTester $I)
    {
        $I->wantTo('Test boolean field in GridView with filter');
        
        $field = new VirtualFieldDefinition([
            'entity_type' => 1,
            'name' => 'verified',
            'label' => 'Verified',
            'data_type' => 'bool',
        ]);
        $field->save();
        
        $definitions = [$field];
        $columns = GridViewHelper::getColumns($definitions);
        
        $I->assertEquals('verified', $columns[0]['attribute']);
        $I->assertEquals('boolean', $columns[0]['format']);
        $I->assertIsArray($columns[0]['filter']);
        $I->assertEquals('No', $columns[0]['filter'][0]);
        $I->assertEquals('Yes', $columns[0]['filter'][1]);
    }

    public function testTextFieldTruncationInGridView(FunctionalTester $I)
    {
        $I->wantTo('Test text field truncation in GridView');
        
        $field = new VirtualFieldDefinition([
            'entity_type' => 1,
            'name' => 'long_text',
            'label' => 'Long Text',
            'data_type' => 'text',
        ]);
        $field->save();
        
        $longText = str_repeat('Lorem ipsum ', 50); // Create long text
        
        $model = new TestModel([
            'name' => 'Text User',
            'email' => 'text@example.com',
        ]);
        $model->long_text = $longText;
        $model->save();
        
        $definitions = [$field];
        $columns = GridViewHelper::getColumns($definitions);
        
        $I->assertEquals('long_text', $columns[0]['attribute']);
        $I->assertIsCallable($columns[0]['value']);
        
        // Test value truncation
        $retrieved = TestModel::findOne($model->id);
        $displayValue = call_user_func($columns[0]['value'], $retrieved);
        
        // Should be truncated to 100 chars + ...
        $I->assertLessThanOrEqual(103, strlen($displayValue));
        if (strlen($longText) > 100) {
            $I->assertStringEndsWith('...', $displayValue);
        }
    }

    public function testDateFieldFormatting(FunctionalTester $I)
    {
        $I->wantTo('Test date field formatting');
        
        $dateField = new VirtualFieldDefinition([
            'entity_type' => 1,
            'name' => 'join_date',
            'label' => 'Join Date',
            'data_type' => 'date',
        ]);
        $dateField->save();
        
        $datetimeField = new VirtualFieldDefinition([
            'entity_type' => 1,
            'name' => 'last_login',
            'label' => 'Last Login',
            'data_type' => 'datetime',
        ]);
        $datetimeField->save();
        
        $definitions = [$dateField, $datetimeField];
        
        // Test in DetailView
        $detailAttrs = DetailViewHelper::getAttributes($definitions);
        
        $I->assertIsArray($detailAttrs[0]['format']);
        $I->assertEquals('date', $detailAttrs[0]['format'][0]);
        $I->assertEquals('php:Y-m-d', $detailAttrs[0]['format'][1]);
        
        $I->assertIsArray($detailAttrs[1]['format']);
        $I->assertEquals('datetime', $detailAttrs[1]['format'][0]);
        $I->assertEquals('php:Y-m-d H:i:s', $detailAttrs[1]['format'][1]);
        
        // Test in GridView
        $gridColumns = GridViewHelper::getColumns($definitions);
        
        $I->assertIsArray($gridColumns[0]['format']);
        $I->assertEquals('date', $gridColumns[0]['format'][0]);
        
        $I->assertIsArray($gridColumns[1]['format']);
        $I->assertEquals('datetime', $gridColumns[1]['format'][0]);
    }

    public function testHelperWithCustomOptions(FunctionalTester $I)
    {
        $I->wantTo('Test helpers with custom options');
        
        $field = new VirtualFieldDefinition([
            'entity_type' => 1,
            'name' => 'custom_field',
            'label' => 'Custom Field',
            'data_type' => 'string',
        ]);
        $field->save();
        
        $definitions = [$field];
        
        // Test DetailView with custom options
        $customOptions = [
            'custom_field' => [
                'format' => 'raw',
                'value' => function($model) {
                    return '<strong>' . $model->custom_field . '</strong>';
                },
            ],
        ];
        
        $attributes = DetailViewHelper::getAttributes($definitions, $customOptions);
        
        $I->assertEquals('custom_field', $attributes[0]['attribute']);
        $I->assertEquals('raw', $attributes[0]['format']);
        $I->assertIsCallable($attributes[0]['value']);
        
        // Test GridView with custom options
        $gridOptions = [
            'custom_field' => [
                'headerOptions' => ['style' => 'width: 200px'],
            ],
        ];
        
        $columns = GridViewHelper::getColumns($definitions, $gridOptions);
        
        $I->assertEquals('custom_field', $columns[0]['attribute']);
        $I->assertArrayHasKey('headerOptions', $columns[0]);
        $I->assertEquals(['style' => 'width: 200px'], $columns[0]['headerOptions']);
    }

    public function testGetSingleAttribute(FunctionalTester $I)
    {
        $I->wantTo('Test getting a single attribute configuration');
        
        $field = new VirtualFieldDefinition([
            'entity_type' => 1,
            'name' => 'single_field',
            'label' => 'Single Field',
            'data_type' => 'string',
        ]);
        $field->save();
        
        // Test DetailViewHelper::getAttribute
        $attribute = DetailViewHelper::getAttribute($field);
        
        $I->assertIsArray($attribute);
        $I->assertEquals('single_field', $attribute['attribute']);
        $I->assertEquals('Single Field', $attribute['label']);
        
        // Test GridViewHelper::getColumn
        $column = GridViewHelper::getColumn($field);
        
        $I->assertIsArray($column);
        $I->assertEquals('single_field', $column['attribute']);
        $I->assertEquals('Single Field', $column['label']);
    }
}
