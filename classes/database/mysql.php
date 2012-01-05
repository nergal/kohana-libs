<?php defined('SYSPATH') or die('No direct access allowed.');

class Database_Mysql extends Kohana_Database_Mysql {

	/**
	 * Инстанция кэша
	 * @var Kohana_Cache
	 */
	protected $_cache_instance = NULL;

	/**
	 * Разрешать кэширование
	 * @var boolean
	 */
	protected $_allow_caching = TRUE;

	/**
	 * Префикс ключа
	 * @var string
	 */
	protected $_key_prefix = 'xo4y_';

	public function __construct($name = NULL, array $config = NULL)
	{
		parent::__construct($name, $config);
		$this->_allow_caching = $config['caching'];

		$this->_cache_instance = Cache::instance('memcache');
	}

	/**
	 * Геттер для self::$_key_prefix
	 *
	 * @param string $key
	 */
	public function get_prefix($key)
	{
		return $this->_key_prefix.md5($key);
	}

	/**
	 * Сеттер для self::$_key_prefix
	 *
	 * @param string $key
	 */
	public function set_prefix($key)
	{
		$this->_key_prefix = $key;
		return $this;
	}

	/**
	 * Парсер SQL-запроса, возвращает список таблиц в запросе
	 *
	 * @param string $data
	 * @return array(integer, array) тип запроса (Database::INSERT и т.д.), список таблиц
	 */
	protected function _get_tables($data)
	{
		$query_tables = array();
		$query_type = NULL;

		$data = str_replace('`', '', $data);                           // Чистим от кавычек
		$data = preg_replace('#(\n|\t)#', ' ', $data);                 // Приводим всё к одной строке
		$data = preg_replace('(("|\')(.*?)("|\'))', '%data%', $data);  // Вычищаем данные, чтобы не путаться
		$data = preg_replace('#(\(|\))#', ' ', $data);                 // Убираем скобки, чтобы не мешали

	    if (preg_match('#INTO (?P<tables>.+?) (VALUES?|SET|SELECT)#i', $data, $insert_matches)) {
	    	$query_type = Database::INSERT;
	    	$data = explode(' ', $insert_matches['tables']);
	    	$data = explode('.', $data[0]);
			$query_tables[] = end($data);
	    } elseif (preg_match('#UPDATE (?P<tables>.+?) SET#i', $data, $update_matches)) {
	    	$query_type = Database::UPDATE;
	    	$data = explode(' ', $update_matches['tables']);
			$data = array_values(array_filter($data));
	    	$data = explode('.', $data[0]);
			$query_tables[] = end($data);
		} else { // SELECT или DELETE
			$query_type = Database::SELECT;

			if (strtoupper(substr($data, 0, 6)) == 'DELETE') {
				$query_type = Database::DELETE;
			}
			// Делим на под-запросы
			$data = preg_split('#(SELECT|DELETE)#i', $data);

		    foreach ($data as $query) {
		    	// Выделение части с именами таблиц
			    if (preg_match('#FROM (?<tables>.+?) ?(?:(WHERE|GROUP|ORDER|LIMIT).+)?$#i', $query, $select_matches)) {
					$tables = $select_matches['tables'];

					// Отделение join'ов
					$sub_tables = preg_split('#(LEFT|RIGHT|INNER|CROSS|STRAIGHT_JOIN|NATURAL|JOIN)#i', $tables, -1, PREG_SPLIT_NO_EMPTY);

					$_sub_tables = array();
					foreach ($sub_tables as $sub_table) {
						$_sub_tables = array_merge($_sub_tables, explode(',', $sub_table));
					}
					$sub_tables = $_sub_tables;

					if (preg_match_all('#JOIN (?P<tables>.+?) ON#', $query, $join_matches)) {
						$sub_tables = array_merge($sub_tables, $join_matches['tables']);
					}

					foreach ($sub_tables as $table) {
						// Выделение таблиц из join
						if (preg_match('#^(?<table>.+?)(?:ON (?:.+))$#i', $table, $join)) {
							$table = $join['table'];
						}
						// Делим перечисление таблиц
					    $aliases = explode(',', $table);

					    foreach ($aliases as $aliase) {
							$aliase = trim($aliase);

							$aliase = preg_replace('#^.+?\.(.+)$#', '$1', $aliase);  // Удаление алиаса БД
							$aliase = preg_split('#( (AS)?)#i', $aliase);            // Удаление алиаса
							$aliase = trim($aliase[0]);                              // Снова очистка пробелов

							// Убедимся, что имя таблицы не начинается с цифры
							// TODO: решить проблемы с IN (enum)
							if ( ! empty($aliase)) {// AND preg_match('#^[_a-z]#i', $aliase)) {
								$query_tables[] = $aliase;
							}
					    }
					}
			    }
		    }
		}

	    $query_tables = array_filter($query_tables);
	    $query_tables = array_unique($query_tables);
	    $query_tables = array_values($query_tables);

	    return array($query_type, $query_tables);
	}


	/**
	 * (non-PHPdoc)
	 * @see modules/auth/classes/kohana/Kohana_Database_Mysql#query()
	 */
	public function query($type, $sql, $as_object = FALSE, array $params = NULL)
	{
		if (isset($params['caching'])) {
			$this->_allow_caching = $params['caching'];
		}

		if ($this->_allow_caching === TRUE) {
			list($auto_type, $tags) = $this->_get_tables($sql);
		}
		$cache_key = $this->get_prefix('Database::query("'.$sql.'")');
		$result = FALSE;

		// Make sure the database is connected
		$this->_connection or $this->connect();

		if ( ! empty($this->_config['connection']['persistent']) AND $this->_config['connection']['database'] !== Database_MySQL::$_current_databases[$this->_connection_id])
		{
			// Select database on persistent connections
			$this->_select_db($this->_config['connection']['database']);
		}

		$from_cache = TRUE;

		if (($this->_allow_caching !== TRUE) OR ($type != Database::SELECT) OR ($result = $this->_cache_instance->get($cache_key)) === NULL) {
			$from_cache = FALSE;

			if ( ! empty($this->_config['profiling']))
			{
				// Benchmark this query for the current instance
				$benchmark = Profiler::start("Database ({$this->_instance})", $sql);
			}

			$result = mysql_query($sql, $this->_connection);

			// Execute the query
			if ($result === FALSE)
			{
				if (isset($benchmark))
				{
					// This benchmark is worthless
					Profiler::delete($benchmark);
				}

				throw new Database_Exception(mysql_errno($this->_connection), '[:code] :error ( :query )', array(
					':code' => mysql_errno($this->_connection),
					':error' => mysql_error($this->_connection),
					':query' => $sql,
				));
			}
		}

		if (isset($benchmark))
		{
			Profiler::stop($benchmark);
		}

		// Set the last query
		$this->last_query = $sql;

		if ($type === Database::SELECT)
		{
			if (is_resource($result)) {
				$returned = new Database_MySQL_Result($result, $sql, $as_object, $params);

				if ($this->_allow_caching === TRUE) {
					$this->_cache_instance->set_with_tags($cache_key, $returned->as_array(), Kohana::$cache_life, $tags);
				}
			} else {
				$returned = new Database_Result_Cached($result, $sql, $as_object);
			}

			return $returned;
		} else {
			if ($this->_allow_caching === TRUE) {
				if ( ! empty($tags)) {
					// Сброс кэша
					foreach ($tags as $tag) {
						$this->_cache_instance->delete_tag($tag, 0);
					}
				}
			}

			if ($type === Database::INSERT OR $type === Database::REPLACE)
			{
				// Return a list of insert id and rows created
				return array(
					mysql_insert_id($this->_connection),
					mysql_affected_rows($this->_connection),
				);
			}
			else
			{
				// Return the number of rows affected
				return mysql_affected_rows($this->_connection);
			}
		}

		return $result;
	}
}