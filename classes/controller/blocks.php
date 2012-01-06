<?php defined('SYSPATH') or die('No direct script access.');

class Controller_Blocks extends Controller
{
	public $names = array();
	public $context = NULL;
	
	protected $_contexts = array('left', 'right', 'full', 'top', 'inner', 'double');
	
	public function before()
	{
		$this->names = (array) $this->request->query('names');
		$this->context = $this->request->query('context');
	}
	
	/**
     * Выборка блоков
     *
     * @static
     * @param string|array $names
     * @param string $context
     * @param array $params
     * @return string
     */
	public function action_render()
	{
        $output = '';

        foreach ($this->names as $key => $item) {
            if (in_array($this->context, $this->_contexts)) {
                if (is_numeric($key)) {
                    $name = $item;
                    $params = array();
                } else {
                    $name = $key;
                    $params = $item;
                }
                $output.= $this->_call($name, $this->context, $params);
            }
        }

        $this->response->body($output);
	}
	
	/**
     * Вызов метода
     *
     * @access protected
     * @param string $name
     * @param string $context
     * @param array $params
     * @return string
     */
	protected function _call($name, $context, Array $params = array())
	{
		$request_name = '/blocks_'.$context.'_'.$name.'/render/';
		
		$request = Request::factory($request_name);
		foreach ($params as $key => $param) {
			$request->query($key, $param);
		}
		
		try {	
			$request->execute();
		} catch (HTTP_Exception_404 $e) {
			// Гасим исключение
		}

		return $request->body();
	} 
}
