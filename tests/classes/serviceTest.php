<?php

/**
 * @group nergal
 * @group nergal.modules
 * @group nergal.modules.service
 */
class KohanaServiceTest extends PHPUnit_Framework_TestCase {

	static protected $test_instance;

	public function setUp()
	{
		$mock = $this->getMock('ORM', array('set', 'save'));
		self::$test_instance = new TestService($mock);
	}

	public function tearDown()
	{
	}
	
	public function testConstruct()
	{
		$this->assertInstanceOf('ORM', self::$test_instance->orm());
	}
	
	public function testCreate()
	{
		$mock = self::$test_instance->orm();
		
		$mock->expects($this->never())
			->method('set');
		$mock->expects($this->once())
			->method('save');
		
		self::$test_instance->orm($mock);
		self::$test_instance->create();
		
		// Array
		$this->setUp();
		$mock = self::$test_instance->orm();
		
		$mock->expects($this->exactly(10))
			->method('set');
		$mock->expects($this->once())
			->method('save');
		
		$test_data = array();
		foreach (range(0, 9) as $i) {
			$test_data[$i] = $i;
		}
		
		self::$test_instance->orm($mock);
		self::$test_instance->create($test_data);
	}
	
	/**
	 * @expectedException PHPUnit_Framework_Error
	 */
	public function testBadCreate() {
		$mock = self::$test_instance->orm();
		
		$mock->expects($this->never())
			->method('set');
		$mock->expects($this->never())
			->method('save');
		
		$test_data = array();
		foreach (range(0, 9) as $i) {
			$test_data = $i;
		}
		
		self::$test_instance->orm($mock);
		self::$test_instance->create($test_data);
	}
	
	public function testRead()
	{
	}
	
	public function testUpdate()
	{
	}
	
	public function testDelate()
	{
	}
}

class TestService extends Kohana_Service {
        public function orm(ORM $orm = NULL)
        {
            if ($orm === NULL) {
                return $this->orm;
            } else {
                $this->orm = $orm;
            }
        }
}