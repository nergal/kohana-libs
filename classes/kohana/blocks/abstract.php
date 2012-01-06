<?php

abstract class Kohana_Blocks_Abstract extends Controller
{
	public $template = NULL;
	
	abstract public function render();
	
	public function before()
	{
		$controller = $this->request->controller();
		$view_name = str_replace('_', DIRECTORY_SEPARATOR, $controller);
		
		if (Kohana::find_file('views', $view_name)) {		
			$this->template = View::factory($view_name);
		}
		
		return parent::before();
	}
	
	public function action_render()
	{
		return $this->render();
	}
	
	public function after()
	{
		if ($this->template !== NULL) {
			$this->request->body($this->template->render());
		}
		
		return parent::after();
	}
}
