<?php
/**
 * @group nergal
 * @group nergal.helper
 */
class HelperTest extends PHPUnit_Framework_TestCase
{
    /**
     * @test
     * @dataProvider validPlural
     */
    public function testPlural($value, $expect)
    {
	$test = Helper::plural($value, 1, 2, 3);
	$this->assertEquals($test, $expect);
    }
    
    public function validPlural()
    {
	return array(
	    array(1, 1),
	    array(2, 2),
	    array(3, 2),
	    array(4, 2),
	    array(5, 3),
	    array(9, 3),
	    array(0, 3),
	    array(-4, 2),
	    array(128, 3),
	    array(621, 1),
	    array('asd', 3),
	    array(NULL, 3),
	);
    }
}