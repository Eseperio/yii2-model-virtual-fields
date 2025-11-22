<?php

namespace tests\functional;

use Codeception\Test\Unit;
use tests\_app\models\TestModel;
use eseperio\virtualfields\models\VirtualFieldDefinition;
use eseperio\virtualfields\models\VirtualFieldValue;
use Yii;
use FunctionalTester;

/**
 * VirtualFieldWorkflowCest
 */
class VirtualFieldWorkflowCest
{
    protected $testModelId;
    protected $fieldDefinitionId;

    public function _before(FunctionalTester $I)
    {
        // Clean up any existing data
        VirtualFieldValue::deleteAll();
        VirtualFieldDefinition::deleteAll();
        TestModel::deleteAll();
    }

    public function testCreateFieldDefinition(FunctionalTester $I)
    {
        $I->wantTo('Create a virtual field definition');
        
        $definition = new VirtualFieldDefinition([
            'entity_type' => 1,
            'name' => 'phone_number',
            'label' => 'Phone Number',
            'data_type' => 'string',
            'required' => false,
            'active' => true,
        ]);
        
        $I->assertTrue($definition->save(), 'Failed to save field definition');
        $I->assertNotNull($definition->id);
        $I->assertEquals('phone_number', $definition->name);
        $I->assertEquals('string', $definition->data_type);
        
        $this->fieldDefinitionId = $definition->id;
    }

    /**
     * @depends testCreateFieldDefinition
     */
    public function testCreateModelWithVirtualField(FunctionalTester $I)
    {
        $I->wantTo('Create a model instance and set virtual field values');
        
        // First create the field definition
        $definition = new VirtualFieldDefinition([
            'entity_type' => 1,
            'name' => 'phone_number',
            'label' => 'Phone Number',
            'data_type' => 'string',
            'required' => false,
            'active' => true,
        ]);
        $definition->save();
        
        // Create model
        $model = new TestModel([
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);
        
        // Set virtual field
        $model->phone_number = '+1234567890';
        
        $I->assertTrue($model->save(), 'Failed to save model');
        $I->assertNotNull($model->id);
        
        $this->testModelId = $model->id;
        
        // Verify the virtual field was saved
        $I->assertEquals('+1234567890', $model->phone_number);
    }

    /**
     * @depends testCreateModelWithVirtualField
     */
    public function testRetrieveVirtualField(FunctionalTester $I)
    {
        $I->wantTo('Retrieve virtual field values from database');
        
        // Create field definition
        $definition = new VirtualFieldDefinition([
            'entity_type' => 1,
            'name' => 'phone_number',
            'label' => 'Phone Number',
            'data_type' => 'string',
            'required' => false,
            'active' => true,
        ]);
        $definition->save();
        
        // Create and save model with virtual field
        $model = new TestModel([
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);
        $model->phone_number = '+1234567890';
        $model->save();
        
        // Retrieve the model from database
        $retrievedModel = TestModel::findOne($model->id);
        
        $I->assertNotNull($retrievedModel);
        $I->assertEquals('+1234567890', $retrievedModel->phone_number);
        $I->assertEquals('John Doe', $retrievedModel->name);
    }

    public function testUpdateVirtualField(FunctionalTester $I)
    {
        $I->wantTo('Update virtual field values');
        
        // Create field definition
        $definition = new VirtualFieldDefinition([
            'entity_type' => 1,
            'name' => 'phone_number',
            'label' => 'Phone Number',
            'data_type' => 'string',
            'required' => false,
            'active' => true,
        ]);
        $definition->save();
        
        // Create model with virtual field
        $model = new TestModel([
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
        ]);
        $model->phone_number = '+1111111111';
        $model->save();
        
        // Update virtual field
        $model->phone_number = '+9999999999';
        $I->assertTrue($model->save());
        
        // Retrieve and verify
        $updatedModel = TestModel::findOne($model->id);
        $I->assertEquals('+9999999999', $updatedModel->phone_number);
    }

    public function testDeleteModelWithVirtualFields(FunctionalTester $I)
    {
        $I->wantTo('Delete a model and verify virtual field values are also deleted');
        
        // Create field definition
        $definition = new VirtualFieldDefinition([
            'entity_type' => 1,
            'name' => 'phone_number',
            'label' => 'Phone Number',
            'data_type' => 'string',
            'required' => false,
            'active' => true,
        ]);
        $definition->save();
        
        // Create model with virtual field
        $model = new TestModel([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
        $model->phone_number = '+5555555555';
        $model->save();
        
        $modelId = $model->id;
        
        // Verify value exists
        $valueCount = VirtualFieldValue::find()
            ->where(['entity_type' => 1, 'entity_id' => $modelId])
            ->count();
        $I->assertEquals(1, $valueCount);
        
        // Delete model
        $model->delete();
        
        // Verify virtual field values were also deleted
        $valueCount = VirtualFieldValue::find()
            ->where(['entity_type' => 1, 'entity_id' => $modelId])
            ->count();
        $I->assertEquals(0, $valueCount);
    }

    public function testMultipleVirtualFields(FunctionalTester $I)
    {
        $I->wantTo('Work with multiple virtual fields on the same model');
        
        // Create multiple field definitions
        $phoneField = new VirtualFieldDefinition([
            'entity_type' => 1,
            'name' => 'phone_number',
            'label' => 'Phone Number',
            'data_type' => 'string',
        ]);
        $phoneField->save();
        
        $ageField = new VirtualFieldDefinition([
            'entity_type' => 1,
            'name' => 'age',
            'label' => 'Age',
            'data_type' => 'int',
        ]);
        $ageField->save();
        
        $birthDateField = new VirtualFieldDefinition([
            'entity_type' => 1,
            'name' => 'birth_date',
            'label' => 'Birth Date',
            'data_type' => 'date',
        ]);
        $birthDateField->save();
        
        // Create model with multiple virtual fields
        $model = new TestModel([
            'name' => 'Multi Field User',
            'email' => 'multi@example.com',
        ]);
        $model->phone_number = '+1234567890';
        $model->age = 30;
        $model->birth_date = '1994-01-15';
        
        $I->assertTrue($model->save());
        
        // Retrieve and verify all fields
        $retrievedModel = TestModel::findOne($model->id);
        $I->assertEquals('+1234567890', $retrievedModel->phone_number);
        $I->assertEquals(30, $retrievedModel->age);
        $I->assertEquals('1994-01-15', $retrievedModel->birth_date);
    }

    public function testBooleanVirtualField(FunctionalTester $I)
    {
        $I->wantTo('Test boolean data type virtual field');
        
        $field = new VirtualFieldDefinition([
            'entity_type' => 1,
            'name' => 'newsletter_subscription',
            'label' => 'Newsletter Subscription',
            'data_type' => 'bool',
        ]);
        $field->save();
        
        $model = new TestModel([
            'name' => 'Bool Test User',
            'email' => 'bool@example.com',
        ]);
        $model->newsletter_subscription = true;
        $model->save();
        
        $retrieved = TestModel::findOne($model->id);
        $I->assertTrue($retrieved->newsletter_subscription);
        
        // Update to false
        $retrieved->newsletter_subscription = false;
        $retrieved->save();
        
        $retrieved->refresh();
        $I->assertFalse($retrieved->newsletter_subscription);
    }

    public function testJsonVirtualField(FunctionalTester $I)
    {
        $I->wantTo('Test JSON data type virtual field');
        
        $field = new VirtualFieldDefinition([
            'entity_type' => 1,
            'name' => 'preferences',
            'label' => 'User Preferences',
            'data_type' => 'json',
        ]);
        $field->save();
        
        $preferences = [
            'theme' => 'dark',
            'language' => 'en',
            'notifications' => true,
        ];
        
        $model = new TestModel([
            'name' => 'JSON Test User',
            'email' => 'json@example.com',
        ]);
        $model->preferences = $preferences;
        $model->save();
        
        $retrieved = TestModel::findOne($model->id);
        $I->assertIsArray($retrieved->preferences);
        $I->assertEquals('dark', $retrieved->preferences['theme']);
        $I->assertEquals('en', $retrieved->preferences['language']);
        $I->assertTrue($retrieved->preferences['notifications']);
    }

    public function testFieldNameValidation(FunctionalTester $I)
    {
        $I->wantTo('Test that field names are properly validated');
        
        // Test invalid field name (starts with number)
        $invalidField = new VirtualFieldDefinition([
            'entity_type' => 1,
            'name' => '123invalid',
            'label' => 'Invalid Field',
            'data_type' => 'string',
        ]);
        
        $I->assertFalse($invalidField->validate());
        $I->assertArrayHasKey('name', $invalidField->errors);
        
        // Test valid field name
        $validField = new VirtualFieldDefinition([
            'entity_type' => 1,
            'name' => 'valid_field_name',
            'label' => 'Valid Field',
            'data_type' => 'string',
        ]);
        
        $I->assertTrue($validField->validate());
    }

    public function testRequiredVirtualField(FunctionalTester $I)
    {
        $I->wantTo('Test required validation on virtual fields');
        
        $field = new VirtualFieldDefinition([
            'entity_type' => 1,
            'name' => 'required_field',
            'label' => 'Required Field',
            'data_type' => 'string',
            'required' => true,
        ]);
        $field->save();
        
        // Try to save without required field
        $model = new TestModel([
            'name' => 'Required Test User',
            'email' => 'required@example.com',
        ]);
        
        $I->assertFalse($model->validate());
        $I->assertArrayHasKey('required_field', $model->errors);
        
        // Now set the required field
        $model->required_field = 'some value';
        $I->assertTrue($model->validate());
        $I->assertTrue($model->save());
    }

    public function testDefaultValue(FunctionalTester $I)
    {
        $I->wantTo('Test default values for virtual fields');
        
        $field = new VirtualFieldDefinition([
            'entity_type' => 1,
            'name' => 'status',
            'label' => 'Status',
            'data_type' => 'string',
            'default_value' => 'active',
        ]);
        $field->save();
        
        $model = new TestModel([
            'name' => 'Default Test User',
            'email' => 'default@example.com',
        ]);
        $model->save();
        
        // Retrieve and check default value
        $retrieved = TestModel::findOne($model->id);
        $I->assertEquals('active', $retrieved->status);
    }

    public function testInactiveFieldsNotLoaded(FunctionalTester $I)
    {
        $I->wantTo('Test that inactive fields are not loaded');
        
        $field = new VirtualFieldDefinition([
            'entity_type' => 1,
            'name' => 'inactive_field',
            'label' => 'Inactive Field',
            'data_type' => 'string',
            'active' => false,
        ]);
        $field->save();
        
        $model = new TestModel([
            'name' => 'Inactive Test User',
            'email' => 'inactive@example.com',
        ]);
        
        // This should not set the value since field is inactive
        $model->save();
        
        $retrieved = TestModel::findOne($model->id);
        
        // Field should not be accessible
        $I->assertNull($retrieved->inactive_field);
    }

    public function testVirtualFieldService(FunctionalTester $I)
    {
        $I->wantTo('Test VirtualFieldService directly');
        
        // Create field definition
        $field = new VirtualFieldDefinition([
            'entity_type' => 1,
            'name' => 'service_test',
            'label' => 'Service Test',
            'data_type' => 'string',
        ]);
        $field->save();
        
        // Create model
        $model = new TestModel([
            'name' => 'Service Test User',
            'email' => 'service@example.com',
        ]);
        $model->save();
        
        // Use service to set value
        $module = Yii::$app->getModule('virtualFields');
        $service = $module->get('service');
        
        $service->setValue(1, $model->id, 'service_test', 'test value');
        
        // Retrieve using service
        $values = $service->getValues(1, $model->id);
        $I->assertArrayHasKey('service_test', $values);
        $I->assertEquals('test value', $values['service_test']);
        
        // Verify through model
        $retrieved = TestModel::findOne($model->id);
        $I->assertEquals('test value', $retrieved->service_test);
    }
}
