<?php defined('SYSPATH') or die('No direct access allowed.');

/**
 * Класс для вставки meta-информации
 *
 * @author nergal
 * @package forum
 * @package forum/meta
 */
class Kohana_Meta
{
	/**
	 * Пулл меты
	 * @static
	 * @var array
	 */
	protected static $_meta = array();

	/**
	 * Кэш модели
	 * @static
	 * @var Model_Meta
	 */
	protected static $_data_cache = NULL;

	/**
	 * Выборка меты
	 *
	 * @static
	 * @param string $mask
	 * @param array $params
	 * @param boolean $colums == TRUE выбирается meta+title
	 * @param boolean $colums == FALSE выбирается h1
	 * @param boolean $colums == NULL выбирается всё
	 * @return array
	 */
	public static function get($mask, Array $params = array(), $colums = NULL)
	{
		if (($data = self::$_data_cache) == NULL) {
			$data = ORM::factory('meta')
						->where('page.name', '=', $mask)
						->find_all();

			self::$_data_cache = $data;
		}

		$meta = array();
		foreach ($data as $item) {
			$html = $item->type->scheme;
			$html = str_replace('{%value%}', $item->data, $html);

			$meta[$item->type->tag] = $html;
		}

		foreach ($params as $key => $item) {
			unset($params[$key]);
			$key = '{%'.$key.'%}';
			$params[$key] = $item;
		};


		foreach ($meta as & $value) {
			$value = strtr($value, $params);
		}

		if ($colums === TRUE) {
			if (isset($meta['h1'])) {
				unset($meta['h1']);
			}
		} elseif ($colums === FALSE) {
			$header = NULL;

			if (isset($meta['h1'])) {
				$header = $meta['h1'];
			}

			return $header;
		}

		self::$_meta = $meta;
		return $meta;
	}

	/**
	 * Отображение отрендеренных данных метаинформации
	 *
	 * @static
	 * @return array
	 */
	public static function render_meta()
	{
		$data = NULL;
		if ( ! empty(self::$_meta)) {
			$data = self::$_meta;
			if (isset($data['h1'])) {
				unset($data['h1']);
			}
			$data = implode("\n", $data);
		}

		return $data;
	}

	/**
	 * Отображение отрендеренных данных заголовка
	 *
	 * @static
	 * @return array
	 */
	public static function render_header()
	{
		$data = NULL;
		if (isset(self::$_meta['h1'])) {
			$data = self::$_meta['h1'];
		}
		return $data;
	}
}