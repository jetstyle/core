<?php
/*
 * Базовый класс View
 */

class View
{

	var $models = array();

	function View(&$rh)
	{
		$this->rh =& $rh;
	}

	function handle()
	{
		$this->beforeHandle();

		$this->_handle();

		//$this->afterHandle();    	
	}

	/*
	 * Общая пачка функционала ДО отработки _handle 
	 */
	function beforeHandle()
	{
		return;
	}

	/*
	 * переопределяется в наследниках
	 */
	function _handle()
	{

	}

	function addModel(&$model, $key='')
	{
		if (is_object($model))
		{
			if(empty($key)) $key = get_class($model);
			$this->models[strtolower($key)] = $model->data;
		}
		else if (is_array($model))
		{

			$this->models[$key] = $model;
		}
	}

}    

?>
