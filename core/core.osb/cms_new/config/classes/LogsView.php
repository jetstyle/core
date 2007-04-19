<?
	
class LogsView {
	
	var $rh; //������ �� $rh
	var $config; //������ �� ������ ������ ModuleConfig
	var $state; //������������ StateSet
	
	var $template = "logs_view.html";
	var $store_to;
	
	var $table_users;
	var $ITEMS = array(); //����������� ������
	
	function LogsView( &$config ){
		//base modules binds
		$this->config =& $config;
		$this->rh =& $config->rh;
		$this->store_to = "logs_".$config->module_name;
		//StateSet
		$this->state =& new StateSet($this->rh);
		$this->state->Set($this->rh->state);
		//������� ������
		$this->table_users = $this->rh->project_name.'_users';
	}
	
	function Handle(){
		
		//������ ���������
		$this->state->Keep('class_id');
		$item_id = $this->state->Keep('item_id');
		$this->state->Keep('user_id');
		
		//������ ������
		$this->Load();
		
		$tpl =& $this->rh->tpl;
		
		//�������� ������
		$this->rh->UseClass('ListObject',0);
		$list =& new ListObject( $this->rh, $this->ITEMS );
		$list->EVOLUTORS['bgcolor'] = array(&$this,'_bgcolor');
		$list->Parse( $this->template.':'.( $item_id ? 'Item' : 'User' ), '_list', true );
		
		//�������� ���������
		$tpl->Parse( $this->template, $this->store_to, true );
	}
	
	function Load(){
		$state =& $this->state;
		$rh =& $this->rh;
		if( !$this->state->Get('item_id') && !$this->state->Get('user_id') )
			$rh->EndError('�� ������ �� $item_id, �� $user_id ��� ������ �����.');
		//������ ������ - �������� ��� ������
		$this->rh->UseClass("DBDataView",0);
		if( $item_id = (integer)$this->state->Get('item_id') ){
			//���������
			if( !($class_id = (integer)$this->state->Get('class_id')) )
				$rh->EndError('�� ������ ����������� ��� ������ �����.');
			//������� ������
			$sql = "SELECT inserted, action, l.title as title, link, user_id, u.login as username";
			$sql .= " FROM ".$rh->logs->table_logs." as l, ".$rh->logs->table_classes." as c, ".$this->table_users." as u ";
			$sql .= " WHERE c.id='$class_id' AND c.id=l.class_id AND l.item_id=".$item_id." AND u.id=l.user_id";
		}
		if( $user_id = (integer)$this->state->Get('user_id') ){
			$sql = "SELECT c.title as class_title, inserted, action, l.title as title, link, user_id";
			$sql .= " FROM ".$rh->logs->table_logs." as l, ".$rh->logs->table_classes." as c";
			$sql .= " WHERE c.id=l.class_id AND l.user_id=".$user_id;
		}
		//������� ������
		$sql .= " ORDER BY l.inserted DESC";
		//������
		$this->ITEMS = $rh->db->Query($sql);
	}
	
	function _bgcolor(&$list){
		return (++$list->_i)%2 ? '#eeeeee' : '#dddddd' ;
	}
}
	
?>