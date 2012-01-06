<?php defined('SYSPATH') or die('No direct script access.');

class Controller_Blocks_Right_Example extends Blocks_Abstract
{
    public function render()
    {
    	$this->template->header = 'test';
    }
}
