<?php

use EventEspresso\core\services\assets\Registry;

/**
 * Runs tests on the Registry class.
 *
 * @package    EventEspresso
 * @subpackage tests
 * @author     Darren Ethier
 * @since      4.9.24.rc.004
 * @group 10306
 */
class RegistryTest extends EE_UnitTestCase
{

    /**
     * @var Registry
     */
    protected $registry;

    public function setUp()
    {
        $this->registry = new Registry();
        parent::setUp();
    }


    public function tearDown()
    {
        parent::tearDown();
        $this->registry = null;
    }


    public function test_addData_no_previous_scalar()
    {
        $this->registry->addData('test', 'has_data');
        $actual = $this->registry->getData('test');
        $this->assertEquals('has_data', $actual);
    }


    public function test_addData_no_previous_array()
    {
        $this->registry->addData('test', array('has_data'));
        $actual = $this->registry->getData('test');
        $this->assertEquals('has_data', $actual[0]);
    }


    /**
     * @expectedException InvalidArgumentException
     */
    public function test_addData_no_overwrite_array()
    {
        $this->registry->addData('test', array('initial_value'));
        $this->registry->addData('test', array('another_value'));
    }


    /**
     * @expectedException InvalidArgumentException
     */
    public function test_addData_no_overwrite_scalar()
    {
        $this->registry->addData('test', 'initial_value');
        $this->registry->addData('test', 'cause_exception');
    }
}