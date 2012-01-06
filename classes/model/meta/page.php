<?php defined('SYSPATH') or die('No direct access allowed.');

/**
 * Модель для страниц мета-информации
 *
 * @author nergal
 * @package forum
 * @package forum/meta
 */
class Model_Meta_Page extends ORM
{
    protected $_table_name = 'meta_pages';
	
    protected $_has_many = array(
	'meta' => array(
	    'model' => 'meta',
	    'foreign_key' => 'meta_pages_id',
        )
    );

    public function rules()
    {
        return array(
            'name' => array(
                array('not_empty'),
                array('min_length', array(':value', 4)),
                array('max_length', array(':value', 3000)),
            ),
            'scheme' => array(
                array('not_empty'),
                array('min_length', array(':value', 4)),
                array('max_length', array(':value', 3000)),
	    ),
        );
    }
}
