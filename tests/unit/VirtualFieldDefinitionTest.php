<?php

namespace tests\unit;

use Codeception\Test\Unit;
use eseperio\virtualfields\models\VirtualFieldDefinition;

class VirtualFieldDefinitionTest extends Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    public function testValidFieldDefinition()
    {
        $definition = new VirtualFieldDefinition([
            'entity_type' => 1,
            'name' => 'test_field',
            'label' => 'Test Field',
            'data_type' => 'string',
            'required' => false,
            'active' => true,
        ]);

        $this->assertTrue($definition->validate());
    }

    public function testInvalidFieldName()
    {
        $definition = new VirtualFieldDefinition([
            'entity_type' => 1,
            'name' => '123invalid', // Starts with number
            'label' => 'Test Field',
            'data_type' => 'string',
        ]);

        $this->assertFalse($definition->validate());
        $this->assertArrayHasKey('name', $definition->errors);
    }

    public function testInvalidDataType()
    {
        $definition = new VirtualFieldDefinition([
            'entity_type' => 1,
            'name' => 'test_field',
            'label' => 'Test Field',
            'data_type' => 'invalid_type',
        ]);

        $this->assertFalse($definition->validate());
        $this->assertArrayHasKey('data_type', $definition->errors);
    }

    public function testOptionsArray()
    {
        $definition = new VirtualFieldDefinition([
            'entity_type' => 1,
            'name' => 'test_field',
            'label' => 'Test Field',
            'data_type' => 'string',
        ]);

        $options = ['min' => 1, 'max' => 100];
        $definition->setOptionsArray($options);

        $this->assertEquals($options, $definition->getOptionsArray());
        $this->assertJson($definition->options);
    }

    public function testAttributeLabels()
    {
        $definition = new VirtualFieldDefinition();
        $labels = $definition->attributeLabels();

        $this->assertArrayHasKey('name', $labels);
        $this->assertArrayHasKey('data_type', $labels);
        $this->assertArrayHasKey('entity_type', $labels);
    }
}
