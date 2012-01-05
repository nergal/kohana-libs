<?php

class Date extends Kohana_Date
{
    protected static function _get_monthes()
    {
	    return array(
		'Jan' => 'января',
		'Feb' => 'февраля',
		'Mar' => 'марта',
		'Apr' => 'апреля',	
		'May' => 'мая',
		'Jun' => 'июня',
		'Jul' => 'июля',
		'Aug' => 'августа',
		'Sep' => 'сентября',
		'Oct' => 'октября',
		'Nov' => 'ноября',
		'Dec' => 'декабря',
	    );
    }

    public static function defuzzy_span($date = 'now', $local_date = 'now')
    {
	$date = new DateTime($date);
	$current_time = new DateTime($local_date);
	
	$diff = $current_time->getTimestamp() - $date->getTimestamp();
	
	$fuzzy = $date->format('j M Y в G:i');
	$time = $date->format('G:i');
	
	$interval = $current_time
	    ->setTime(0,0,0)
	    ->diff($date->setTime(0,0,0))
	    ->format('%a');
	
	if ($diff >= 0) {
	    if ($diff <= Date::MINUTE) {
		$fuzzy = 'только что';
	    } elseif ($diff <= (Date::MINUTE * 5)) {
		$minutes = round($diff / 60);
		$fuzzy = $minutes.' '.Helper::plural($minutes, 'минуту', 'минуты', 'минут').' назад';
	    } elseif ($interval < 1) {
		$fuzzy = 'сегодня в '.$time;
	    } elseif ($interval < 2) {
		$fuzzy = 'вчера в '.$time;
	    }
	}

	$fuzzy = strtr($fuzzy, self::_get_monthes());
	return $fuzzy;
    }
    
    public static function deformatted_date($date_str = 'now', $timestamp_format = NULL, $timezone = NULL)
    {
		$date = parent::formatted_time($date_str, $timestamp_format, $timezone);
		return strtr($date, self::_get_monthes());
    }
    
    public static function deformatted_time($datetime_str = 'now', $timestamp_format = NULL, $timezone = NULL)
    {
		$date = parent::formatted_time($datetime_str, $timestamp_format, $timezone);
		return strtr($date, self::_get_monthes());
    }
    
}
