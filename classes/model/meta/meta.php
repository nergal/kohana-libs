<?php defined('SYSPATH') or die('No direct access allowed.');

/**
 * Модель для мета-информации
 *
 * @author nergal
 * @package forum
 * @package forum/meta
 */
class Model_Meta_Meta extends ORM
{
    protected $_table_name = 'meta';
    protected $_load_with = array('type', 'page');

    protected $_belongs_to = array(
		'type' => array(
		    'model' => 'meta_type',
		    'foreign_key' => 'metatag_id',
		),		
		'page' => array(
		    'model' => 'meta_page',
		    'foreign_key' => 'meta_pages_id',
		),
    );

    /**
     * Правила валидации
     * @return array
     */
    public function rules()
    {
        return array(
            'metatag_id' => array(
                array('not_empty'),
                array('regex', array(':value', '/^[0-9]+$/')),
            ),
            'page_id' => array(
                array('not_empty'),
                array('regex', array(':value', '/^[0-9]+$/')),
            ),
            'data' => array(
                array('not_empty'),
                array('min_length', array(':value', 4)),
                array('max_length', array(':value', 3000)),
            ),
        );
    }
}
