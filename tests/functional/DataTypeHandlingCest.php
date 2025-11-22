<?php

namespace tests\functional;

use Codeception\Test\Unit;
use tests\_app\models\Product;
use eseperio\virtualfields\models\VirtualFieldDefinition;
use eseperio\virtualfields\models\VirtualFieldValue;

class DataTypeHandlingCest
{
    public function _before(FunctionalTester $I)
    {
        // Clean up any existing data
        VirtualFieldValue::deleteAll();
        VirtualFieldDefinition::deleteAll();
        Product::deleteAll();
    }

    public function testStringDataType(FunctionalTester $I)
    {
        $I->wantTo('Test string data type handling');
        
        $field = new VirtualFieldDefinition([
            'entity_type' => 2,
            'name' => 'sku',
            'label' => 'SKU',
            'data_type' => 'string',
        ]);
        $field->save();
        
        $product = new Product([
            'name' => 'Test Product',
            'price' => 99.99,
        ]);
        $product->sku = 'PROD-12345';
        $product->save();
        
        $retrieved = Product::findOne($product->id);
        $I->assertIsString($retrieved->sku);
        $I->assertEquals('PROD-12345', $retrieved->sku);
    }

    public function testIntegerDataType(FunctionalTester $I)
    {
        $I->wantTo('Test integer data type handling');
        
        $field = new VirtualFieldDefinition([
            'entity_type' => 2,
            'name' => 'stock_quantity',
            'label' => 'Stock Quantity',
            'data_type' => 'int',
        ]);
        $field->save();
        
        $product = new Product([
            'name' => 'Test Product',
            'price' => 99.99,
        ]);
        $product->stock_quantity = 150;
        $product->save();
        
        $retrieved = Product::findOne($product->id);
        $I->assertIsInt($retrieved->stock_quantity);
        $I->assertEquals(150, $retrieved->stock_quantity);
        
        // Test with string that should be cast to int
        $product->stock_quantity = '200';
        $product->save();
        
        $retrieved->refresh();
        $I->assertIsInt($retrieved->stock_quantity);
        $I->assertEquals(200, $retrieved->stock_quantity);
    }

    public function testFloatDataType(FunctionalTester $I)
    {
        $I->wantTo('Test float data type handling');
        
        $field = new VirtualFieldDefinition([
            'entity_type' => 2,
            'name' => 'weight',
            'label' => 'Weight (kg)',
            'data_type' => 'float',
        ]);
        $field->save();
        
        $product = new Product([
            'name' => 'Test Product',
            'price' => 99.99,
        ]);
        $product->weight = 2.5;
        $product->save();
        
        $retrieved = Product::findOne($product->id);
        $I->assertIsFloat($retrieved->weight);
        $I->assertEquals(2.5, $retrieved->weight);
    }

    public function testBooleanDataType(FunctionalTester $I)
    {
        $I->wantTo('Test boolean data type handling');
        
        $field = new VirtualFieldDefinition([
            'entity_type' => 2,
            'name' => 'in_stock',
            'label' => 'In Stock',
            'data_type' => 'bool',
        ]);
        $field->save();
        
        // Test true value
        $product = new Product([
            'name' => 'Test Product',
            'price' => 99.99,
        ]);
        $product->in_stock = true;
        $product->save();
        
        $retrieved = Product::findOne($product->id);
        $I->assertIsBool($retrieved->in_stock);
        $I->assertTrue($retrieved->in_stock);
        
        // Test false value
        $product->in_stock = false;
        $product->save();
        
        $retrieved->refresh();
        $I->assertIsBool($retrieved->in_stock);
        $I->assertFalse($retrieved->in_stock);
    }

    public function testDateDataType(FunctionalTester $I)
    {
        $I->wantTo('Test date data type handling');
        
        $field = new VirtualFieldDefinition([
            'entity_type' => 2,
            'name' => 'release_date',
            'label' => 'Release Date',
            'data_type' => 'date',
        ]);
        $field->save();
        
        $product = new Product([
            'name' => 'Test Product',
            'price' => 99.99,
        ]);
        $product->release_date = '2024-01-15';
        $product->save();
        
        $retrieved = Product::findOne($product->id);
        $I->assertEquals('2024-01-15', $retrieved->release_date);
    }

    public function testDateTimeDataType(FunctionalTester $I)
    {
        $I->wantTo('Test datetime data type handling');
        
        $field = new VirtualFieldDefinition([
            'entity_type' => 2,
            'name' => 'last_restock',
            'label' => 'Last Restock',
            'data_type' => 'datetime',
        ]);
        $field->save();
        
        $product = new Product([
            'name' => 'Test Product',
            'price' => 99.99,
        ]);
        $product->last_restock = '2024-01-15 14:30:00';
        $product->save();
        
        $retrieved = Product::findOne($product->id);
        $I->assertEquals('2024-01-15 14:30:00', $retrieved->last_restock);
    }

    public function testJsonDataType(FunctionalTester $I)
    {
        $I->wantTo('Test JSON data type handling');
        
        $field = new VirtualFieldDefinition([
            'entity_type' => 2,
            'name' => 'specifications',
            'label' => 'Specifications',
            'data_type' => 'json',
        ]);
        $field->save();
        
        $specs = [
            'color' => 'blue',
            'size' => 'large',
            'features' => ['waterproof', 'durable'],
        ];
        
        $product = new Product([
            'name' => 'Test Product',
            'price' => 99.99,
        ]);
        $product->specifications = $specs;
        $product->save();
        
        $retrieved = Product::findOne($product->id);
        $I->assertIsArray($retrieved->specifications);
        $I->assertEquals('blue', $retrieved->specifications['color']);
        $I->assertEquals('large', $retrieved->specifications['size']);
        $I->assertIsArray($retrieved->specifications['features']);
        $I->assertCount(2, $retrieved->specifications['features']);
    }

    public function testTextDataType(FunctionalTester $I)
    {
        $I->wantTo('Test text data type handling');
        
        $field = new VirtualFieldDefinition([
            'entity_type' => 2,
            'name' => 'description',
            'label' => 'Description',
            'data_type' => 'text',
        ]);
        $field->save();
        
        $longText = str_repeat('Lorem ipsum dolor sit amet. ', 100);
        
        $product = new Product([
            'name' => 'Test Product',
            'price' => 99.99,
        ]);
        $product->description = $longText;
        $product->save();
        
        $retrieved = Product::findOne($product->id);
        $I->assertEquals($longText, $retrieved->description);
    }

    public function testDataTypeSerialization(FunctionalTester $I)
    {
        $I->wantTo('Test that data is properly serialized and deserialized');
        
        // Create fields of different types
        $fields = [
            ['name' => 'string_field', 'data_type' => 'string'],
            ['name' => 'int_field', 'data_type' => 'int'],
            ['name' => 'float_field', 'data_type' => 'float'],
            ['name' => 'bool_field', 'data_type' => 'bool'],
            ['name' => 'json_field', 'data_type' => 'json'],
        ];
        
        foreach ($fields as $fieldData) {
            $field = new VirtualFieldDefinition([
                'entity_type' => 2,
                'name' => $fieldData['name'],
                'label' => ucfirst($fieldData['name']),
                'data_type' => $fieldData['data_type'],
            ]);
            $field->save();
        }
        
        // Create product with all field types
        $product = new Product([
            'name' => 'Multi-type Product',
            'price' => 199.99,
        ]);
        $product->string_field = 'text value';
        $product->int_field = 42;
        $product->float_field = 3.14;
        $product->bool_field = true;
        $product->json_field = ['key' => 'value', 'number' => 123];
        $product->save();
        
        // Retrieve and verify types
        $retrieved = Product::findOne($product->id);
        
        $I->assertIsString($retrieved->string_field);
        $I->assertIsInt($retrieved->int_field);
        $I->assertIsFloat($retrieved->float_field);
        $I->assertIsBool($retrieved->bool_field);
        $I->assertIsArray($retrieved->json_field);
        
        $I->assertEquals('text value', $retrieved->string_field);
        $I->assertEquals(42, $retrieved->int_field);
        $I->assertEquals(3.14, $retrieved->float_field);
        $I->assertTrue($retrieved->bool_field);
        $I->assertEquals(['key' => 'value', 'number' => 123], $retrieved->json_field);
    }

    public function testNullValues(FunctionalTester $I)
    {
        $I->wantTo('Test handling of null values');
        
        $field = new VirtualFieldDefinition([
            'entity_type' => 2,
            'name' => 'optional_field',
            'label' => 'Optional Field',
            'data_type' => 'string',
            'required' => false,
        ]);
        $field->save();
        
        $product = new Product([
            'name' => 'Test Product',
            'price' => 99.99,
        ]);
        $product->save();
        
        // Should be null since not set
        $retrieved = Product::findOne($product->id);
        $I->assertNull($retrieved->optional_field);
        
        // Set a value
        $product->optional_field = 'some value';
        $product->save();
        
        $retrieved->refresh();
        $I->assertEquals('some value', $retrieved->optional_field);
        
        // Set to null explicitly
        $product->optional_field = null;
        $product->save();
        
        $retrieved->refresh();
        $I->assertNull($retrieved->optional_field);
    }

    public function testEmptyStringVsNull(FunctionalTester $I)
    {
        $I->wantTo('Test distinction between empty string and null');
        
        $field = new VirtualFieldDefinition([
            'entity_type' => 2,
            'name' => 'notes',
            'label' => 'Notes',
            'data_type' => 'string',
        ]);
        $field->save();
        
        $product = new Product([
            'name' => 'Test Product',
            'price' => 99.99,
        ]);
        $product->notes = '';
        $product->save();
        
        $retrieved = Product::findOne($product->id);
        $I->assertIsString($retrieved->notes);
        $I->assertEquals('', $retrieved->notes);
    }
}
