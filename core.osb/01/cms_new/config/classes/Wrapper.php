<?
/*
  ����� ������ ��� ���������� �������. 
  ����� �������� � ���� ������ ������ ������... ��� ����������� 8))
*/
  
class Wrapper {
  
  //templates
  var $template; //������ �� �������
  var $store_to;
  var $store_to_tree = "___tree"; //���� ������ ����� ������
  var $store_to_form = "___form"; //���� ������ ����� �����
  
  var $wrapped_count = 0; //����� �������� �������
  var $CONFIGS = array(); //������ �������� �������� �������
  var $MODULES = array(); //������ �������� �������� �������
  
  function Wrapper( &$config ){
    //base modules binds
    $this->config =& $config;
    $this->rh =& $config->rh;
    //sublings
    $this->wrapped_count = $n = count($config->WRAPPED);
    for($i=0;$i<$n;$i++){
      $this->CONFIGS[$i] = $config;
      $this->CONFIGS[$i]->Read( $this->config->WRAPPED[$i] );
      $this->MODULES[$i] =& $this->CONFIGS[$i]->InitModule();
    }
    //templates
    $this->template = $config->template;
    $this->store_to = $config->GetPassed()."_".$config->module_name;
  }
  
  function Handle(){
    $config =& $this->config;
    $tpl =& $this->rh->tpl;
    //�������������� ����� �������������� ��������
    //� ������ ���������� ������ � ���������������� ���������
    $_mode = $this->config->GetPassed();
    for($i=$this->wrapped_count-1;$i>=0;$i--){
      $module =& $this->MODULES[$i];
//      $module->store_to = $_mode."_".$module->config->GetPassed();
      $module->store_to = '__wrapped_'.$i;//'__'.$module->config->GetPassed();
      $module->prefix = $config->module_name.$module->store_to.'_';
      //?? ��������� ��� ������ � ���� ������?
      //?? �� � ������, ������ ��������� �� ����...
//      $module->_href_template = ( $this->_href_template )? $this->_href_template : $this->rh->path_rel."do/".$this->config->module_name.'/'.$_mode.'?';
      $module->Handle();
      $tpl->assign( 'wid_'.$i, $this->config->module_name.'_'.$i );
      $tpl->assign( '_wtitle_'.$i, $config->module_title.": ".$this->rh->MODES_RUS[$module->config->GetPassed()] );
    }
    //����� �����
    $this->rh->tpl->Parse( $this->template, $this->store_to, true );
    //������ ��������� ��������� ���������� �� �����
    for($i=$this->wrapped_count-1;$i>=0;$i--){
      $tpl_var = '__wrapped_'.$i;
      if( $this->store_to!=$tpl_var )
        $tpl->free( $tpl_var );
    }
  }
  
} 
?>