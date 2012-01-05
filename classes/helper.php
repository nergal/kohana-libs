<?php

class Helper
{
    const TITLE       = 1;
    const POST        = 2;
    const EXTREME     = 4;
    const REQUEST     = 8;
    const BODY        = 16;
    const RSS_TITLE = 32;
    const RSS_BODY  = 64;
    const COMMENT    = 128;

    /**
     * Фильтрация содержимого
     *
     * @param string $body
     * @param integer $type
     * @return string
     */
    public static function filter($body, $type = Helper::TITLE)
    {
        $filters = (array) Helper::get_filters_for($type);

        foreach ($filters as $callback) {
            if (is_callable($callback)) {
                $body = call_user_func($callback, $body);
            }
        }

        return $body;
    }

    /**
     * Выборка списка фильтров
     *
     * @param integer $type
     * @return array
     */
    protected static function get_filters_for($type)
    {
        $filters = array();
        if ($type & Helper::TITLE) {
            $filters['stripTags'] = 'strip_tags';
            $filters['light_escape'] = array('Helper', 'light_escape');
        }

        if ($type & Helper::RSS_TITLE) {
            $filters['stripTags'] = 'strip_tags';
            $filters['rss_html'] = array('Helper', 'clear_rss_html');
        }

        if ($type & Helper::RSS_BODY) {
            $filters['stripTags'] = 'strip_tags';
            $filters['rss_html'] = array('Helper', 'clear_rss_html');
        }

        if ($type & Helper::POST) {
            $filters['stripTags'] = array('Helper', 'strip_tag');
            $filters['escape'] = array('Helper', 'escape');
            $filters['auto_p'] = array('Text', 'auto_p');
            $filters['normalize'] = array('Helper', 'normalize');
            $filters['slashes'] = array('Helper', 'strip_slashes');
        }

        if ($type & Helper::REQUEST) {
            $filters['stripTags'] = array('Helper', 'strip_tag');
            $filters['escape'] = array('Helper', 'escape');
        }

        if ($type & Helper::EXTREME) {
            $filters['xss'] = array('Security', 'xss_clean');
        }

        if ($type & Helper::BODY) {
            $filters['stripTags'] = array('Helper', 'strip_tags_article');
        }

        if ($type & Helper::COMMENT) {
            $filters['stripTags'] = array('Helper', 'strip_tags_article');
            $filters['bbcode'] = 'bbcode_parse';
        }

        return $filters;
    }

    public static function clear_rss_html($var)
    {
        $var = html_entity_decode($var, ENT_COMPAT, 'UTF-8');
        return $var;
    }

    /**
     * Escapes a value for output in a view script.
     *
     * @param mixed $var The output to escape.
     * @return mixed The escaped value.
     */
    public static function escape($var)
    {
        return call_user_func('htmlspecialchars', $var, ENT_COMPAT, Kohana::$charset);
    }

    /**
     *  вариант htmlspecialchars без обработки &
     *
     * @param mixed $var The output to escape.
     * @return mixed The escaped value.
     */
    public static function light_escape($var)
    {
        $var = preg_replace('/"/', '&quot;',  $var);
        $var = preg_replace('/>/', '&gt;',  $var);
        $var = preg_replace('/</', '&lt;',  $var);
        $var = preg_replace('/\'/', '&#039;',  $var);

        return $var;
    }

    /**
     * Очистка тегов старой базы
     *
     * @param string $var
     * @return string
     */
    public static function strip_tag($var)
    {
        // br2nl
        $break = "\n";
        $var = preg_replace('#\<br( /)?\>#i', $break, $var);

        return strip_tags($var);
    }

    public static function strip_tags_article($var)
    {
        $var = strip_tags($var, '<p><a><b><i><u><s><table><th><tr><td><thead><tbody><strong><span><br><img><div><iframe><object><embed>');
        $var = str_replace('<a ', '<a rel="nofollow" ', $var);

        return $var;
    }

    /**
     * Нормализация постов из старой базы
     *
     * @param string $var
     * @return string
     */
    public static function normalize($var)
    {
        return str_replace('&amp;quot;', '&quot;', $var);
    }

    /**
     * Очистка слешей из старой базы
     *
     * @param string $var
     * @return string
     */
    public static function strip_slashes($str)
    {
        return preg_replace('#\\\\{2,}#', ' ', $str);
    }

    public static function plural($number, $form1, $form2, $form3)
    {
        $number = intVal($number);
        $number = abs($number);

        $form = $form3;
        if ($number % 10 == 1 AND $number % 100 != 11) {
            $form = $form1;
        } elseif ($number % 10 >= 2 AND $number % 10 <= 4 AND ($number % 100 < 10 OR $number % 100 >= 20)) {
            $form = $form2;
        }

        return $form;
    }
}
