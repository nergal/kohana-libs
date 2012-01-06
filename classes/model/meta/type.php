<?php defined('SYSPATH') or die('No direct access allowed.');

/**
 * Модель для типов мета-информации
 *
 * @author nergal
 * @package forum
 * @package forum/meta
 */
class Model_Meta_Type extends ORM
{
    protected $_table_name = 'meta_types';

    public function rules()
    {
        return array(
            'tag' => array(
                array('not_empty'),
                array('min_length', array(':value', 4)),
                array('max_length', array(':value', 3000)),
            ),
        );
    }
}
