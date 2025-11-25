<?php

namespace tests\unit;

use Codeception\Test\Unit;
use eseperio\virtualfields\Module;
use yii\base\InvalidConfigException;

class ModuleTest extends Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    public function testModuleInitialization()
    {
        $module = new Module('virtualFields', null, [
            'entityMap' => [
                1 => 'tests\\_app\\models\\TestModel',
            ],
        ]);
        
        $module->init();
        
        $this->assertNotNull($module);
    }

    public function testInvalidEntityMapNonInteger()
    {
        $this->expectException(InvalidConfigException::class);
        
        $module = new Module('virtualFields', null, [
            'entityMap' => [
                'string_key' => 'tests\\_app\\models\\TestModel',
            ],
        ]);
        
        $module->init();
    }

    public function testInvalidEntityMapNonExistentClass()
    {
        $this->expectException(InvalidConfigException::class);
        
        $module = new Module('virtualFields', null, [
            'entityMap' => [
                1 => 'NonExistentClass',
            ],
        ]);
        
        $module->init();
    }

    public function testGetEntityClass()
    {
        $module = new Module('virtualFields', null, [
            'entityMap' => [
                1 => 'tests\\_app\\models\\TestModel',
            ],
        ]);
        
        $module->init();
        
        $this->assertEquals('tests\\_app\\models\\TestModel', $module->getEntityClass(1));
        $this->assertNull($module->getEntityClass(999));
    }

    public function testGetEntityType()
    {
        $module = new Module('virtualFields', null, [
            'entityMap' => [
                1 => 'tests\\_app\\models\\TestModel',
            ],
        ]);
        
        $module->init();
        
        $this->assertEquals(1, $module->getEntityType('tests\\_app\\models\\TestModel'));
        $this->assertNull($module->getEntityType('NonExistentClass'));
    }
}
