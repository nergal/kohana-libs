<?php

/**
 * Расширение ORM
 *
 * @author     nergal
 * @package    btlady
 * @subpackage ORM
 */
class ORM extends Kohana_ORM
{
	/**
	 * Кэш для выбранных опций
	 * @var array
	 */
	protected $_cached_options = array();

	/**
	 * Список внешних полей-options
	 * array(
	 *     'field' => 'type',
	 *     ...
	 * )
	 *
	 * @var array
	 */
	protected $_foreign_fields = array();

	/**
	 * Массив данных для вставки в options
	 * array(
	 *     'field' => 'value',
	 *     ...
	 * )
	 *
	 * @var array
	 */
	protected $_foreign_fields_data = array();

	/**
	 * Допустимые типы для self::$_foreign_fields
	 * @var array
	 */
	protected $_allowable_types = array('string', 'float', 'int', 'text');

	/*************************** START ALIASED ENGINE ***************************/

	/**
	 * Фальшивые колонки-ссылки на внешнюю таблицу
	 * array(
	 * 		'fake1' => 'original1',
	 * 		'fake2' => 'original2',
	 * )
	 *
	 * @var array
	 */
	protected $_aliased = array();

	/**
	 * Checks if object data is set.
	 *
	 * @param  string $column Column name
	 * @return boolean
	 */
	public function __isset($column)
	{
		return (isset($this->_object[$column]) OR
			isset($this->_aliased[$column]) OR
			isset($this->_related[$column]) OR
			isset($this->_has_one[$column]) OR
			isset($this->_belongs_to[$column]) OR
			isset($this->_has_many[$column]) OR
			isset($this->_foreign_fields[$column]));
	}

	/**
	 * Unsets object data.
	 *
	 * @param  string $column Column name
	 * @return void
	 */
	public function __unset($column)
	{
		if (in_array($column, array_keys($this->_aliased))) {
			$column = $this->_aliased[$column];
		}

		unset($this->_object[$column], $this->_changed[$column], $this->_related[$column]);
	}

	/**
	 * Allows serialization of only the object data and state, to prevent
	 * "stale" objects being unserialized, which also requires less memory.
	 *
	 * @return array
	 */
	public function serialize()
	{
		// Store only information about the object
		foreach (array('_primary_key_value', '_object', '_aliased', '_changed', '_loaded', '_saved', '_sorting') as $var)
		{
			$data[$var] = $this->{$var};
		}

		return serialize($data);
	}

	/**
	 * Handles retrieval of all model values, relationships, and metadata.
	 *
	 * @param   string $column Column name
	 * @return  mixed
	 */
	public function __get($column)
	{
		if (array_key_exists($column, $this->_aliased)) {
			$column = $this->_aliased[$column];
		}

		// Подгрузка options
		if (isset($this->_foreign_fields[$column]) AND ( ! array_key_exists($column, $this->_related))) {
			$query = DB::query(Database::SELECT,
				'SELECT
					`options`.`value`,
					`ot`.`label`
				 FROM `options_types` `ot`
					LEFT JOIN `type_values` `tv` ON (`tv`.`id` = `ot`.`type_id`)
					LEFT JOIN (
						SELECT `option_id`, `value` FROM `pages_string_options` WHERE `page_id` = :page_id UNION ALL
						SELECT `option_id`, `value` FROM `pages_int_options` WHERE `page_id` = :page_id UNION ALL
						SELECT `option_id`, `value` FROM `pages_text_options` WHERE `page_id` = :page_id UNION ALL
						SELECT `option_id`, `value` FROM `pages_float_options` WHERE `page_id` = :page_id
					) AS `options` ON (`ot`.`id` = `options`.`option_id`)
	 			 WHERE `ot`.`table_name` = :table_name AND `ot`.`label` IN :labels');

			if (isset($this->id) AND $this->id !== NULL) {
				$query->parameters(array(
				    ':page_id' => $this->id,
					':table_name' => $this->_table_name,
					':labels' => array_keys($this->_foreign_fields),
				));

				$data = $query->execute();

				foreach ($data as $item) {
					$this->_related[$item['label']] = $item['value'];
				}
			}
		}

		if (array_key_exists($column, $this->_related)) {
			return $this->_related[$column];
		}

		return parent::__get($column);
	}

	/**
	 * Handles setting of column
	 *
	 * @param  string $column Column name
	 * @param  mixed  $value  Column value
	 * @return void
	 */
	public function set($column, $value)
	{
		if (array_key_exists($column, $this->_aliased)) {
			$column = $this->_aliased[$column];
		}

		if (isset($this->_foreign_fields[$column]) AND ( ! array_key_exists($column, $this->_related))) {
			$this->_foreign_fields_data[$column] = $value;
			return;
		}

		return parent::set($column, $value);
	}

	/**
	 * Returns the values of this object as an array, including any related one-one
	 * models that have already been loaded using with()
	 *
	 * @return array
	 */
	public function as_array()
	{
		$object = array();

		foreach ($this->_object as $column => $value)
		{
			// Call __get for any user processing
			$object[$column] = $this->__get($column);
		}

		foreach ($this->_related as $column => $model)
		{
			// Include any related objects that are already loaded
			$object[$column] = $model->as_array();
		}

		foreach ($this->_aliased as $column => $original) {
			// Заполнение фальшивых колонок
			$object[$column] = $object[$original];
		}

		return $object;
	}

	/**
	 * Reload column definitions.
	 *
	 * @chainable
	 * @param   boolean $force Force reloading
	 * @return  ORM
	 */
	public function reload_columns($force = FALSE)
	{
		$this->_aliased = $this->aliases();
		return parent::reload_columns($force);
	}

	/**
	 * Алиасы для методов перекрытия
	 *
	 * @return array
	 */
	public function aliases()
	{
		return array();
	}

	/**
	 * Обновление добавленных options в БД
	 *
	 * @return void
	 */
	protected function _update_foreign_fields()
	{
		if ($this->loaded()) {
			foreach ($this->_foreign_fields_data as $key => $object) {
				if (array_key_exists($key, $this->_foreign_fields)) {
					$type = $this->_foreign_fields[$key];
					$table_name = 'pages_'.$type.'_options';

					$query = DB::query(Database::INSERT, 'REPLACE INTO `'.$table_name.'`(`page_id`, `option_id`, `value`) VALUES(:page_id, (SELECT `id` FROM `options_types` WHERE `label` = :key), :value)');
					$query->parameters(array(
						':page_id' => $this->id,
						':value' => $object,
						':key' => $key,
					));

					$query->execute();
				}
			}
		} else {
			throw new Kohana_Exception('Wrond usage of ORM::_update_foreign_fields()');
		}
	}

	/**
	 * Insert a new object to the database
	 *
	 * @chainable
	 * @param  Validation $validation Validation object
	 * @return ORM
	 */
	public function create(Validation $validation = NULL)
	{
		try {
			parent::create($validation);
			$this->_update_foreign_fields();
		} catch (Kohana_Exception $e) {
			throw $e;
		}

		return $this;
	}

	/**
	 * Updates a single record or multiple records
	 *
	 * @chainable
	 * @param  Validation $validation Validation object
	 * @return ORM
	 */
	public function update(Validation $validation = NULL)
	{
		try {
			parent::update($validation);
			$this->_update_foreign_fields();
		} catch (Kohana_Exception $e) {
			throw $e;
		}

		return $this;
	}

	/*************************** END ALIASED ENGINE ***************************/

	public static function factory($model, $id = NULL)
	{
		return parent::factory($model, $id);
	}
	
    public function get_user_title()
    {
		return strip_tags($this->get_user_link());
    }

    /**
     * Ссылка на пользователя
     *
     * @param  boolean $link Формировать ссылку
     * @return string
     */
    public function get_user_link($link = TRUE)
    {
    	$is_annon = TRUE;
    	$text = 'Аноним';

    	if ($this->loaded()) {
    		$user = ($this instanceof Model_User) ? $this : $this->user;
			if ($user->loaded()) {
			    $name = $user->username;
			    if (empty($name)) {
					$name = 'Пользователь №'.$user->id;
			    }

			    if ($link = View::factory()->uri($user)) {
				    $_name = trim(strstr($user->email, '@twitter.com', TRUE));
				    if (strlen($_name)) $name = $_name;
		            
		            $_name = trim($user->lastname.' '.$user->firstname);
		            if (strlen($_name)) $name = $_name;
	                
	                $text = HTML::anchor($link, $name, array('class' => 'user authed'));
		        }
	        }

			if (isset($this->author) AND ! empty($this->author)) {
			    $text = $this->author;
			    
			    if ($link == TRUE) {
					$text = '<span class="user annon">'.$text.'</span>';
			    }
			}
    	}

    	return $text;
    }
}