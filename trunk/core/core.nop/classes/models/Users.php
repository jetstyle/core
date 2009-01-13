<?
$this->UseClass("models/DBModel");

class Users extends DBModel
{
	var $fields = array(
		'id',
		'user_id',
		'login',
		'realm',
		'password',
		'stored_invariant',
		'temp_password',
		'temp_timeout',
		'active',
		'title',
		'title_pre',
		'descr',
		'descr_pre',
		'email',
		'email_confirmed',
		'roles',
		'login_datetime',
		'_created',
		'_modified',
		'_created_user_id',
		'_edited_user_id',
		'_state',
		'_order',
	);
	var $table = 'users';
	var $order = array('_order');
	var $where = '_state = 0';
	var $fields_info = array(
		array('name' => 'id', 'source' => 'user_id'),
	);

	function load($where='', $limit='', $offset='')
	{
		parent::load($where, $limit, $offset);

		foreach ($this->data as $k=>$v)
		{
			$file = "users/picture_".$v['user_id'];
			$this->data[$k]['img'] = $this->rh->upload->getFile($file);
			$file = "users/picture_hp_".$v['user_id'];
			$this->data[$k]['img_hp'] = $this->rh->upload->getFile($file);
			$file = "users/picture_inair_".$v['user_id'];
			$this->data[$k]['img_inair'] = $this->rh->upload->getFile($file);
		}
	}

}  

?>
