<?php
  $this->UseClass( 'TreeControl' );
 
class ToolbarTreeControl extends TreeControl  {

  //for TreeControl
  //���-����� �� ��������� ������ ��� ����� ������ 
  function _Handle(){

    $tpl =& $this->rh->tpl;
    
    $template_ban = $this->template_control.':buttons_ban';
    //������
    $tpl->set('_id',0);
    $tpl->set('_array',implode(',',array(1,1,1)));
    $tpl->Parse( $template_ban, 'buttons_ban', true );
  }
  
}
  
?>
