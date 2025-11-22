<?php

namespace tests\functional;

use Codeception\Test\Unit;
use tests\_app\models\TestModel;
use eseperio\virtualfields\models\VirtualFieldDefinition;
use eseperio\virtualfields\models\VirtualFieldValue;
use Yii;

class ValidationAndEdgeCasesCest
{
    public function _before(FunctionalTester $I)
    {
        // Clean up any existing data
        VirtualFieldValue::deleteAll();
        VirtualFieldDefinition::deleteAll();
        TestModel::deleteAll();
    }

    public function testDuplicateFieldName(FunctionalTester $I)
    {
        $I->wantTo('Test that duplicate field names for same entity are rejected');
        
        $field1 = new VirtualFieldDefinition([
            'entity_type' => 1,
            'name' => 'duplicate_field',
            'label' => 'First Field',
            'data_type' => 'string',
        ]);
        $I->assertTrue($field1->save());
        
        // Try to create another with same name
        $field2 = new VirtualFieldDefinition([
            'entity_type' => 1,
            'name' => 'duplicate_field',
            'label' => 'Second Field',
            'data_type' => 'string',
        ]);
        $I->assertFalse($field2->save());
        $I->assertArrayHasKey('name', $field2->errors);
    }

    public function testSameFieldNameDifferentEntities(FunctionalTester $I)
    {
        $I->wantTo('Test that same field name can be used for different entity types');
        
        $field1 = new VirtualFieldDefinition([
            'entity_type' => 1,
            'name' => 'status',
            'label' => 'User Status',
            'data_type' => 'string',
        ]);
        $I->assertTrue($field1->save());
        
        $field2 = new VirtualFieldDefinition([
            'entity_type' => 2,
            'name' => 'status',
            'label' => 'Product Status',
            'data_type' => 'string',
        ]);
        $I->assertTrue($field2->save());
    }

    public function testInvalidDataType(FunctionalTester $I)
    {
        $I->wantTo('Test that invalid data types are rejected');
        
        $field = new VirtualFieldDefinition([
            'entity_type' => 1,
            'name' => 'test_field',
            'label' => 'Test Field',
            'data_type' => 'invalid_type',
        ]);
        
        $I->assertFalse($field->validate());
        $I->assertArrayHasKey('data_type', $field->errors);
    }

    public function testFieldNameWithSpecialCharacters(FunctionalTester $I)
    {
        $I->wantTo('Test that field names with special characters are rejected');
        
        $invalidNames = [
            'field-name',    // hyphen
            'field name',    // space
            'field.name',    // dot
            'field@name',    // at symbol
        ];
        
        foreach ($invalidNames as $invalidName) {
            $field = new VirtualFieldDefinition([
                'entity_type' => 1,
                'name' => $invalidName,
                'label' => 'Test Field',
                'data_type' => 'string',
            ]);
            
            $I->assertFalse($field->validate(), "Field name '$invalidName' should be invalid");
            $I->assertArrayHasKey('name', $field->errors);
        }
    }

    public function testValidFieldNames(FunctionalTester $I)
    {
        $I->wantTo('Test that valid field names are accepted');
        
        $validNames = [
            'field_name',
            'fieldName',
            'field123',
            '_field',
            'field_',
        ];
        
        foreach ($validNames as $validName) {
            $field = new VirtualFieldDefinition([
                'entity_type' => 1,
                'name' => $validName,
                'label' => 'Test Field',
                'data_type' => 'string',
            ]);
            
            $I->assertTrue($field->validate(), "Field name '$validName' should be valid");
            $I->assertTrue($field->save());
        }
    }

    public function testIntegerValidation(FunctionalTester $I)
    {
        $I->wantTo('Test integer field validation');
        
        $field = new VirtualFieldDefinition([
            'entity_type' => 1,
            'name' => 'age',
            'label' => 'Age',
            'data_type' => 'int',
            'required' => true,
        ]);
        $field->save();
        
        // Try invalid integer
        $model = new TestModel([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
        $model->age = 'not a number';
        
        $I->assertFalse($model->validate());
        $I->assertArrayHasKey('age', $model->errors);
        
        // Valid integer
        $model->age = 25;
        $I->assertTrue($model->validate());
    }

    public function testFloatValidation(FunctionalTester $I)
    {
        $I->wantTo('Test float field validation');
        
        $field = new VirtualFieldDefinition([
            'entity_type' => 1,
            'name' => 'score',
            'label' => 'Score',
            'data_type' => 'float',
            'required' => true,
        ]);
        $field->save();
        
        // Try invalid float
        $model = new TestModel([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
        $model->score = 'not a number';
        
        $I->assertFalse($model->validate());
        $I->assertArrayHasKey('score', $model->errors);
        
        // Valid float
        $model->score = 95.5;
        $I->assertTrue($model->validate());
    }

    public function testDateValidation(FunctionalTester $I)
    {
        $I->wantTo('Test date field validation');
        
        $field = new VirtualFieldDefinition([
            'entity_type' => 1,
            'name' => 'birth_date',
            'label' => 'Birth Date',
            'data_type' => 'date',
            'required' => true,
        ]);
        $field->save();
        
        // Try invalid date
        $model = new TestModel([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
        $model->birth_date = 'invalid-date';
        
        $I->assertFalse($model->validate());
        $I->assertArrayHasKey('birth_date', $model->errors);
        
        // Valid date
        $model->birth_date = '1990-01-15';
        $I->assertTrue($model->validate());
    }

    public function testMassAssignment(FunctionalTester $I)
    {
        $I->wantTo('Test mass assignment of virtual fields');
        
        $field1 = new VirtualFieldDefinition([
            'entity_type' => 1,
            'name' => 'field1',
            'label' => 'Field 1',
            'data_type' => 'string',
        ]);
        $field1->save();
        
        $field2 = new VirtualFieldDefinition([
            'entity_type' => 1,
            'name' => 'field2',
            'label' => 'Field 2',
            'data_type' => 'int',
        ]);
        $field2->save();
        
        $model = new TestModel();
        $data = [
            'TestModel' => [
                'name' => 'Mass Assignment User',
                'email' => 'mass@example.com',
                'field1' => 'value1',
                'field2' => 123,
            ],
        ];
        
        $I->assertTrue($model->load($data));
        $I->assertTrue($model->save());
        
        $retrieved = TestModel::findOne($model->id);
        $I->assertEquals('value1', $retrieved->field1);
        $I->assertEquals(123, $retrieved->field2);
    }

    public function testConcurrentUpdates(FunctionalTester $I)
    {
        $I->wantTo('Test concurrent updates to virtual fields');
        
        $field = new VirtualFieldDefinition([
            'entity_type' => 1,
            'name' => 'counter',
            'label' => 'Counter',
            'data_type' => 'int',
        ]);
        $field->save();
        
        $model = new TestModel([
            'name' => 'Concurrent User',
            'email' => 'concurrent@example.com',
        ]);
        $model->counter = 0;
        $model->save();
        
        // Load the same model twice
        $model1 = TestModel::findOne($model->id);
        $model2 = TestModel::findOne($model->id);
        
        // Update from both
        $model1->counter = 1;
        $model1->save();
        
        $model2->counter = 2;
        $model2->save();
        
        // Last write should win
        $final = TestModel::findOne($model->id);
        $I->assertEquals(2, $final->counter);
    }

    public function testFieldOptionsJsonStorage(FunctionalTester $I)
    {
        $I->wantTo('Test that field options are stored as JSON');
        
        $field = new VirtualFieldDefinition([
            'entity_type' => 1,
            'name' => 'test_field',
            'label' => 'Test Field',
            'data_type' => 'string',
        ]);
        
        $options = [
            'min' => 1,
            'max' => 100,
            'placeholder' => 'Enter value',
        ];
        
        $field->setOptionsArray($options);
        $I->assertTrue($field->save());
        
        $retrieved = VirtualFieldDefinition::findOne($field->id);
        $retrievedOptions = $retrieved->getOptionsArray();
        
        $I->assertIsArray($retrievedOptions);
        $I->assertEquals($options, $retrievedOptions);
    }

    public function testVirtualFieldCaching(FunctionalTester $I)
    {
        $I->wantTo('Test that virtual field definitions are cached');
        
        $field = new VirtualFieldDefinition([
            'entity_type' => 1,
            'name' => 'cached_field',
            'label' => 'Cached Field',
            'data_type' => 'string',
        ]);
        $field->save();
        
        $module = Yii::$app->getModule('virtualFields');
        $service = $module->get('service');
        
        // First call should fetch from DB and cache
        $definitions1 = $service->getDefinitions(1);
        $I->assertNotEmpty($definitions1);
        
        // Second call should use cache
        $definitions2 = $service->getDefinitions(1);
        $I->assertEquals($definitions1, $definitions2);
        
        // Clear cache and verify
        $service->clearCache(1);
        $definitions3 = $service->getDefinitions(1);
        $I->assertNotEmpty($definitions3);
    }

    public function testBehaviorPropertyAccess(FunctionalTester $I)
    {
        $I->wantTo('Test canGetProperty and canSetProperty methods');
        
        $field = new VirtualFieldDefinition([
            'entity_type' => 1,
            'name' => 'test_property',
            'label' => 'Test Property',
            'data_type' => 'string',
        ]);
        $field->save();
        
        $model = new TestModel([
            'name' => 'Property Test User',
            'email' => 'property@example.com',
        ]);
        $model->save();
        
        // Test canGetProperty
        $I->assertTrue($model->canGetProperty('test_property'));
        $I->assertTrue($model->canGetProperty('name')); // Regular attribute
        $I->assertFalse($model->canGetProperty('nonexistent_property'));
        
        // Test canSetProperty
        $I->assertTrue($model->canSetProperty('test_property'));
        $I->assertTrue($model->canSetProperty('name')); // Regular attribute
        $I->assertFalse($model->canSetProperty('nonexistent_property'));
    }

    public function testVirtualFieldsInUnsavedModel(FunctionalTester $I)
    {
        $I->wantTo('Test virtual fields behavior in unsaved models');
        
        $field = new VirtualFieldDefinition([
            'entity_type' => 1,
            'name' => 'temp_field',
            'label' => 'Temp Field',
            'data_type' => 'string',
        ]);
        $field->save();
        
        $model = new TestModel([
            'name' => 'Unsaved User',
            'email' => 'unsaved@example.com',
        ]);
        
        // Should be able to set virtual field before saving
        $model->temp_field = 'temporary value';
        $I->assertEquals('temporary value', $model->temp_field);
        
        // Save and verify
        $I->assertTrue($model->save());
        
        $retrieved = TestModel::findOne($model->id);
        $I->assertEquals('temporary value', $retrieved->temp_field);
    }

    public function testVirtualFieldDeleteOnDefinitionDelete(FunctionalTester $I)
    {
        $I->wantTo('Test that values are deleted when definition is deleted (cascade)');
        
        $field = new VirtualFieldDefinition([
            'entity_type' => 1,
            'name' => 'cascade_field',
            'label' => 'Cascade Field',
            'data_type' => 'string',
        ]);
        $field->save();
        $definitionId = $field->id;
        
        // Create model with virtual field value
        $model = new TestModel([
            'name' => 'Cascade Test User',
            'email' => 'cascade@example.com',
        ]);
        $model->cascade_field = 'test value';
        $model->save();
        
        // Verify value exists
        $valueCount = VirtualFieldValue::find()
            ->where(['definition_id' => $definitionId])
            ->count();
        $I->assertEquals(1, $valueCount);
        
        // Delete definition (should cascade to values due to FK)
        $field->delete();
        
        // Verify values were deleted
        $valueCount = VirtualFieldValue::find()
            ->where(['definition_id' => $definitionId])
            ->count();
        $I->assertEquals(0, $valueCount);
    }
}
