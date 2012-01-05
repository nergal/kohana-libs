<?php
/**
 * @group nergal
 */
class Database_QueryTest extends PHPUnit_Framework_TestCase
{
    public $query_instance = NULL;

    public function setUp()
    {
		$this->query_instance = new Fake_Database_Mysql;
    }

    /**
     * @test
     * @dataProvider providerSelect
     */
    public function testTablesOnSelect($data, $expect)
    {
		$main_type = Database::SELECT;
		list($type, $test) = $this->query_instance->_get_tables($data, $main_type);
		sort($test, SORT_STRING);
		sort($expect, SORT_STRING);
		$this->assertEquals($main_type, $type);
		$this->assertEquals($test, $expect);
    }

    public function providerSelect()
    {
		return $this->_provider('select');
    }

    /**
     * @test
     * @dataProvider providerInsert
     */
    public function testTablesOnInsert($data, $expect)
    {
		$main_type = Database::INSERT;
		list($type, $test) = $this->query_instance->_get_tables($data, $main_type);
		$this->assertEquals($main_type, $type);
		$this->assertEquals($test, $expect);
    }

    public function providerInsert()
    {
		return $this->_provider('insert');
    }

    /**
     * @test
     * @dataProvider providerUpdate
     */
    public function testTablesOnUpdate($data, $expect)
    {
		$main_type = Database::UPDATE;
		list($type, $test) = $this->query_instance->_get_tables($data, $main_type);
		$this->assertEquals($main_type, $type);
		$this->assertEquals($test, $expect);
    }

    public function providerUpdate()
    {
		return $this->_provider('update');
    }

    /**
     * @test
     * @dataProvider providerDelete
     */
    public function testTablesOnDelete($data, $expect)
    {
    	$main_type = Database::DELETE;
		list($type, $test) = $this->query_instance->_get_tables($data, $main_type);
		$this->assertEquals($main_type, $type);
		$this->assertEquals($test, $expect);
    }

    public function providerDelete()
    {
		return $this->_provider('delete');
    }



    protected function _provider($filename)
    {
		$dir = APPPATH.'/tests/test_data/';
		$filename = $dir.'data_'.$filename.'.xml';

		if ( ! file_exists($filename)) {
	    	return array();
		}

		$file = simplexml_load_file($filename);
		$data = array();

		foreach ($file->item as $item) {
	    	$tables = explode(',', $item->expect);
	    	foreach ($tables as &$table) {
				$table = trim($table);
	    	}

	   		$_data = array(
				(string) $item->data,
				$tables,
	    	);

	   		$_data = array_filter($_data);

	    	if ( ! empty($_data)) {
				$data[] = $_data;
	    	}
		}

		return $data;
    }
}

class Fake_Database_Mysql extends Database_Mysql
{
    public function __construct() { }

    public function _get_tables($sql, $type = Database::SELECT)
    {
		$this->_type = $type;
		return parent::_get_tables($sql);
    }
}