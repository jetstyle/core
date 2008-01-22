<?
	
class ModuleConfig {
	
	var $rh; //������ �� $rh
	var $handlers_type = 'modules'; //����������� ��� $rh->FindScript
	var $module_name = ''; //��� �������� ������
	var $PASSED = array(); //������ ��������� ��������, � ������� �����������
	
	function ModuleConfig(&$rh,$module_name){
		$this->rh =& $rh;
		if( !$module_name )
			$this->rh->debug->Error('ModuleConfig: �� ������� module_name.');
		//�������� �����
		if( !$rh->prp->IsGrantedTo('do/'.$module_name ) ){
			$rh->debug->Trace_R( $rh->prp, 1, 'Principal' );
			echo $rh->tpl->parse('access_denied.html');
			$rh->End();
//			$rh->EndError('acces denied');
		}
		//�� ��
		$this->module_name = $module_name;
	}
	
	function Read( $what ){
		if( $what=="" ){
			$this->rh->debug->Trace('ModuleConfig::Read - $what �����');
			return;
		}
		
		//�������� �����
		if( !$this->rh->prp->IsGrantedTo('do/'.$this->module_name.'/'.$what ) )
			$this->rh->End('acces denied:'.'do/'.$this->module_name.'/'.$what );
		
		//�������� ����� ���� �������
		//�������� ��� � ����
		//� ������� �.�. ���������� ���� $this->name = "Jhonson";
//var_dump($what);
//var_dump($this->class_name);
//var_dump($this->handlers_type, $this->module_name.'/'.$what);
		include( $this->rh->FindScript( $this->handlers_type, $this->module_name.'/'.$what ) );
		$this->rh->debug->Trace('ModuleConfig::Read - '.$this->module_name.'/'.$what );
//var_dump($this->class_name);
		
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
					$this->rh->debug->Error('ModuleConfig/'.$this->module_name.'/defs: module_title �����.');
				if( $this->class_name=='' )
					$this->rh->debug->Error('ModuleConfig/'.$this->module_name.'/defs: class_name �����.');
			break;
			
			case 'list':
				if( $this->table_name=='' )
					$this->rh->debug->Error('ModuleConfig/'.$this->module_name.'/list: table_name �����.');
				if( count($this->SELECT_FIELDS)<=0 )
					$this->rh->debug->Error('ModuleConfig/'.$this->module_name.'/list: SELECT_FIELDS �����.');
			break;
			
			case 'form':
				if( $this->table_name=='' )
					$this->rh->debug->Error('ModuleConfig/'.$this->module_name.'/form: table_name �����.');
				if( count($this->SELECT_FIELDS)<=0 )
					$this->rh->debug->Error('ModuleConfig/'.$this->module_name.'/form: SELECT_FIELDS �����.');
			break;
		}
	}
	
	function &InitModule(){
		$rh =& $this->rh;
		//������ �����
		if( $this->get_class_here ){
			require_once( $this->rh->FindScript( $this->handlers_type, $this->module_name.'/'.$this->class_name ) );
			$this->get_class_here = false;
			$this->rh->debug->Trace('ModuleConfig::InitModule - '.$this->module_name.'/'.$this->class_name );
		}
		else{
			$this->rh->UseClass( $this->class_name );
			$this->rh->debug->Trace('ModuleConfig::InitModule - '.$this->module_name.'/'.$this->class_name );
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