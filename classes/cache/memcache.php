<?php defined('SYSPATH') or die('No direct script access.');

/**
 * Тегирования для кэша
 *
 * @author nergal
 * @package forum
 */
class Cache_Memcache extends Kohana_Cache_Memcache implements Kohana_Cache_Tagging
{

	/**
	 * Префикс для ключей кэша
	 * @var string
	 */
	protected $_cache_prefix = 'tag_key_';

	/**
	 * Возвращает исходный объект memcache
	 *
	 * @return Memcache
	 */
	public function get_raw_instance()
	{
		return $this->_memcache;
	}

	/**
	 * Выборка префикса кэша для тега
	 *
	 * @param string $tag
	 * @return string
	 */
	public function get_prefix($tag)
	{
		return $this->_cache_prefix.$tag;
	}

	/**
	 * Установка префикса кэширования
	 *
	 * @param string $prefix
	 * @return Cache_Memcache
	 */
	public function set_prefix($prefix)
	{
		$this->_cache_prefix = $prefix;
		return $this;
	}

	/**
	 * Retrieve a cached value entry by id.
	 *
	 *     // Retrieve cache entry from memcache group
	 *     $data = Cache::instance('memcache')->get('foo');
	 *
	 *     // Retrieve cache entry from memcache group and return 'bar' if miss
	 *     $data = Cache::instance('memcache')->get('foo', 'bar');
	 *
	 * @param   string   id of cache to entry
	 * @param   string   default value to return if cache miss
	 * @return  mixed
	 * @throws  Kohana_Cache_Exception
	 */
	public function get($id, $default = NULL)
	{
		if ($this->_config['profiling'] === TRUE) {
			$benchmark = Profiler::start("Cache Items", $id);
		}

		$data = parent::get($id, $default);

		if (isset($benchmark)) {
			if ( ! $data) {
				Profiler::delete($benchmark);
			} else {
				Profiler::stop($benchmark);
			}
		}

		return $data;
	}

	/**
	 * Set a value based on an id. Optionally add tags.
	 *
	 * Note : Some caching engines do not support
	 * tagging
	 *
	 * @param   string   id
	 * @param   mixed    data
	 * @param   integer  lifetime [Optional]
	 * @param   array    tags [Optional]
	 * @return  boolean
	 */
	public function set_with_tags($id, $data, $lifetime = NULL, array $tags = NULL)
	{
		if (parent::set($id, $data, $lifetime)) {
			foreach ($tags as $tag) {
				if ( ! $this->_set_tag_key($tag, $id)) {
					return FALSE;
				}
			}
		}

		return FALSE;
	}

	/**
	 * Delete cache entries based on a tag
	 *
	 * @param   string   tag
	 * @param   integer  timeout [Optional]
	 */
	public function delete_tag($tag, $timeout = 0)
	{
		$keys = $this->find($tag);

		foreach ($keys as $id) {
			$profiled = $this->_config['profiling'];

			if ($profiled === TRUE) {
				$benchmark = Profiler::start("Deleted Cache Tags", $tag);
			}

			if ( ! parent::delete($id, $timeout)) {
				$error = 'Cant delete cache key "'.$id.'" with tag "'.$tag.'"';
				// Kohana::$log->add(Log::DEBUG, $error)->write();

				if (isset($benchmark)) {
					Profiler::delete($benchmark);
					$profiled = FALSE;
				}
			}

			if ($profiled === TRUE) {
				Profiler::stop($benchmark);
			}
		}

		return TRUE;
	}

	/**
	 * (non-PHPdoc)
	 * @see modules/cache/classes/kohana/cache/Kohana_Cache_Memcache#delete()
	 */
	public function delete($id, $timeout = 0)
	{
		if ($this->_config['profiling'] === TRUE) {
			$benchmark = Profiler::start("Cache Items", $id);
		}

		$data = parent::delete($id, $timeout);

		if (isset($benchmark)) {
			if ( ! $data) {
				Profiler::delete($benchmark);
			} else {
				Profiler::stop($benchmark);
			}
		}

		return $data;
	}

	/**
	 * Увеличивает значение на $value
	 *
	 * @param string $key
	 * @param integer $value
	 * @param integer $default
	 * @param array $tags
	 *
	 * @return boolean
	 */
	public function increment($key, $value = 1, $default = 1, array $tags = array())
	{
		if (($result = $this->_memcache->increment($key, $value)) == FALSE) {
			$result = $this->_memcache->set($key, ($default + $value), 0, Cache_Memcache::CACHE_CEILING);
			$tags+= array('counts', 'increment');

			foreach ($tags as $tag) {
				$this->_set_tag_key($tag, $key);
			}
		}

		return $result;
	}

	/**
	 * Уменьшает значение на $value
	 *
	 * @param string $key
	 * @param integer $value
	 * @param integer $default
	 * @param array $tags
	 *
	 * @return boolean
	 */
	public function decrement($key, $value = 1, $default = 1, array $tags = array())
	{
		if (($result = $this->_memcache->decrement($key, $value)) == FALSE) {
			$result = $this->_memcache->set($key, ($default - $value), 0, Cache_Memcache::CACHE_CEILING);
			$tags+= array('counts', 'decrement');

			foreach ($tags as $tag) {
				$this->_set_tag_key($tag, $key);
			}
		}

		return $result;
	}

	/**
	 * Find cache entries based on a tag
	 *
	 * @param   string   tag
	 * @return  array
	 */
	public function find($tag)
	{
		$key = $this->get_prefix($tag);
		$keys = $this->get($key);

		if ( ! is_array($keys)) {
			$keys = (array) $keys;
		}

		return $keys;
	}

	/**
	 * Привязывание записи к тегу
	 *
	 * @param string $tag
	 * @param string $id
	 * @return boolean
	 */
	protected function _set_tag_key($tag, $id)
	{
		$tag_keys = $this->find($tag);

		if ( ! in_array($id, $tag_keys)) {
			array_push($tag_keys, $id);

			if ($this->_config['profiling'] === TRUE) {
				$benchmark = Profiler::start("Set Cache Tags", $tag);
			}

			$key = $this->get_prefix($tag);
			if ( ! $this->set($key, $tag_keys, 0)) {
				if (isset($benchmark)) {
					Profiler::delete($benchmark);
				}

				$error = 'Can\'t store cache key "'.$id.'" with tag "'.$tag.'"';
				Kohana::$log->add(Log::ERROR, $error)->write();

				return FALSE;
			}

			if (isset($benchmark)) {
				Profiler::stop($benchmark);
			}
		}

		return TRUE;
	}
}