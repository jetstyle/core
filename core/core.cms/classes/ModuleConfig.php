<?php
	
class ModuleConfig {
	
	var $rh; //������ �� $rh
	var $handlers_type = 'modules'; //����������� ��� $rh->FindScript
	var $module_name = ''; //��� �������� ������
	var $PASSED = array(); //������ ��������� ��������, � ������� �����������
	
	function ModuleConfig(&$rh,$module_name)
	{	
		$this->rh =& $rh;
		if( !$module_name )
		{
			throw new Exception('ModuleConfig: �� ������� module_name.');
		}
		
		//�������� �����
		if( !$rh->prp->IsGrantedTo('do/'.$module_name ) )
		{
//			Debug::trace( $rh->prp, 1, 'Principal' );
			echo $rh->tpl->parse('access_denied.html');
			$rh->End();
		}
		
		//�� ��
		$this->module_name = $module_name;
		
		// add module dir to DIRS stack
		$module_dir = $this->rh->DIRS[0].$this->handlers_type.'/'.$this->module_name.'/';
		$module_dir_core = $this->rh->DIRS[1].$this->handlers_type.'/'.$this->module_name.'/';
		array_unshift($this->rh->DIRS, $module_dir, $module_dir_core);
		array_unshift($this->rh->tpl->DIRS, $module_dir, $module_dir_core);
	}
	
	function Read( $what )
	{
		if( $what=="" )
		{
			Debug::trace('ModuleConfig::Read - $what �����');
			return;
		}
		
		//�������� �����
		if( !$this->rh->prp->IsGrantedTo('do/'.$this->module_name.'/'.$what ) )
		{
			$this->rh->End('acces denied:'.'do/'.$this->module_name.'/'.$what );
		}
		
		//�������� ����� ���� �������
		//�������� ��� � ����
		//� ������� �.�. ���������� ���� $this->name = "Jhonson";

		include( $this->rh->findScript( $this->handlers_type, $this->module_name.'/'.$what ) );
		Debug::trace('ModuleConfig::Read - '.$this->module_name.'/'.$what );		

		//���������
		$this->Check($what);
		
		//���������� ��������� �������
		$this->PASSED[] = $what;
	}
	
	//��������, ����� ������� ��� ������� � ������ �����������...
	function Check( $what="defs" ){
		switch( $what ){
			
			case 'defs':
				if( $this->module_title=='' )
					throw new Exception('ModuleConfig/'.$this->module_name.'/defs: module_title �����.');
				if( $this->class_name=='' )
					throw new Exception('ModuleConfig/'.$this->module_name.'/defs: class_name �����.');
			break;
			
			case 'list':
				if( $this->table_name=='' )
					throw new Exception('ModuleConfig/'.$this->module_name.'/list: table_name �����.');
				if( count($this->SELECT_FIELDS)<=0 )
					throw new Exception('ModuleConfig/'.$this->module_name.'/list: SELECT_FIELDS �����.');
			break;
			
			case 'form':
				if( $this->table_name=='' )
					throw new Exception('ModuleConfig/'.$this->module_name.'/form: table_name �����.');
				if( count($this->SELECT_FIELDS)<=0 )
					throw new Exception('ModuleConfig/'.$this->module_name.'/form: SELECT_FIELDS �����.');
			break;
		}
	}
	
	function &InitModule()
	{
		$rh =& $this->rh;
		//������ �����

		if( $this->get_class_here )
		{
			require_once( $this->rh->FindScript( $this->handlers_type, $this->module_name.'/'.$this->class_name ) );
			$this->get_class_here = false;
			Debug::trace('ModuleConfig::InitModule - '.$this->module_name.'/'.$this->class_name );
		}
		else
		{
			$this->rh->UseClass( $this->class_name );
			Debug::trace('ModuleConfig::InitModule - '.$this->module_name.'/'.$this->class_name );
		}
		
		//������ ������ � ���������� ������
		$class_name = $this->class_name;
		unset($this->class_name); //��� �� ������ ����������� ����������� � ����������� ��������
		eval('$this->module =& new '.$class_name.'($this);');
		return $this->module;
	}
	
	function GetPassed( $i=-1 ){
		return $this->PASSED[ ($i<0)? count($this->PASSED)-1: $i ];
	}
}
	
?>