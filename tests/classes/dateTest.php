<?php
/**
 * @group nergal
 * @group kohana.date
 */
class DateTest extends PHPUnit_Framework_TestCase
{
    /**
     * @test
     * @dataProvider validFuzzy
     */
    public function testFuzzy($date, $local_date, $expect)
    {
	$test = Date::defuzzy_span($date, $local_date);
	$this->assertEquals($test, $expect);
    }
    
    public function validFuzzy()
    {
	return array(
	    array('10 Oct 2010', NULL, '10 октября 2010 в 0:00'),
	    array('-1 minute', NULL, 'только что'),
	    array('-2 minute', NULL, '2 минуты назад'),
	    array('1970-01-01 00:00:00', NULL, '1 января 1970 в 0:00'),
	    array('-5 minute', NULL, '5 минут назад'),
	    array('2010-10-10 12:44:00', '2010-10-10 12:34:00', '10 октября 2010 в 12:44'),
	    array('2010-10-10 11:34:00', '2010-10-10 12:34:00', 'сегодня в 11:34'),
	    array('2010-10-09 10:34:00', '2010-10-10 12:34:00', 'вчера в 10:34'),
	);
    }
}