<?php

$this->useClass('models/DBModel');
class Confirm extends DBModel
{
	var $table = 'confirm';
	var $fields = array('realm', 
		'mode',
		'expire', 
		'data',
		'_created', '_modified', '_state', '_order', '_supertag');
	var $where = '1=1';
	var $order = array('expire');

	function initialize(&$ctx, $config=NULL)
	{
		parent::initialize($ctx, $config);
		$this->expire();
	}

	function expire()
	{
		$this->delete(' expire < NOW()');
	}

	function save(&$data)
	{
		if (isset($data['realm']))
			return $this->update($data, array('realm'));
		else
		{
			$data['realm'] = md5(uniqid(rand(), true));
			return $this->insert($data);
		}
	}

}

?>
