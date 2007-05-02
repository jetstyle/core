<?php

$this->useClass('models/DBModel');
class MailOutbox extends DBModel
{
	var $table = 'outbox';
	var $fields = array(
		'realm', 
		'from',
		'to', 
		'subject', 
		'html', 
		'text', 
		'_created', '_modified', '_state', '_order'
	);
	var $where = '_state=0';
	var $order = array('_created');


	function save(&$data)
	{
		if (isset($data['realm']))
			return $this->update($data, array('realm'));
		else
		{
			$data['realm'] = md5(uniqid(rand(), true));
			$this->insert($data);
			return $data['realm'];
		}
	}

}

?>
