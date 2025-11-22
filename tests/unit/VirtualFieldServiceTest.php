<?php

namespace tests\unit;

use Codeception\Test\Unit;
use eseperio\virtualfields\services\VirtualFieldService;

class VirtualFieldServiceTest extends Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    /**
     * @var VirtualFieldService
     */
    protected $service;

    protected function _before()
    {
        $this->service = new VirtualFieldService();
    }

    public function testDataTypeSerialization()
    {
        // Test string
        $this->assertEquals('test', $this->service->serializeValue('test', 'string'));
        $this->assertEquals('test', $this->service->deserializeValue('test', 'string'));

        // Test int
        $this->assertEquals('42', $this->service->serializeValue(42, 'int'));
        $this->assertEquals(42, $this->service->deserializeValue('42', 'int'));

        // Test float
        $this->assertEquals('3.14', $this->service->serializeValue(3.14, 'float'));
        $this->assertEquals(3.14, $this->service->deserializeValue('3.14', 'float'));

        // Test bool
        $this->assertEquals('1', $this->service->serializeValue(true, 'bool'));
        $this->assertEquals('0', $this->service->serializeValue(false, 'bool'));
        $this->assertTrue($this->service->deserializeValue('1', 'bool'));
        $this->assertFalse($this->service->deserializeValue('0', 'bool'));

        // Test json
        $data = ['key' => 'value', 'number' => 42];
        $serialized = $this->service->serializeValue($data, 'json');
        $this->assertJson($serialized);
        $this->assertEquals($data, $this->service->deserializeValue($serialized, 'json'));
    }

    public function testGetDataTypes()
    {
        $types = $this->service->getDataTypes();
        
        $this->assertContains('string', $types);
        $this->assertContains('int', $types);
        $this->assertContains('float', $types);
        $this->assertContains('bool', $types);
        $this->assertContains('date', $types);
        $this->assertContains('datetime', $types);
        $this->assertContains('json', $types);
        $this->assertContains('text', $types);
    }

    public function testRegisterCustomDataType()
    {
        $this->service->registerDataType(
            'custom',
            function($value) { return strtoupper($value); },
            function($value) { return strtoupper($value); },
            function($value) { return strtolower($value); }
        );

        $types = $this->service->getDataTypes();
        $this->assertContains('custom', $types);

        $this->assertEquals('TEST', $this->service->serializeValue('test', 'custom'));
        $this->assertEquals('test', $this->service->deserializeValue('TEST', 'custom'));
    }
}
