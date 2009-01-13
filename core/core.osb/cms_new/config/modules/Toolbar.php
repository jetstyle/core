<?
/*
  Toolbar - ����������� ������ ������������. ������ ��� ���.
  -------
  ��������������, ��� �����-�� ��������� ��� ��������.
*/
  
/*
CREATE TABLE _toolbar (
  id int(11) NOT NULL auto_increment,
  title varchar(255) NOT NULL default '',
  href varchar(255) NOT NULL default '',
  _state tinyint(1) NOT NULL default '0',
  _modified timestamp(14) NOT NULL,
  _created timestamp(14) NOT NULL,
  _order int(11) NOT NULL default '0',
  PRIMARY KEY (id),
  KEY _state(_state),
  KEY _order(_order)
) TYPE=MyISAM;
*/
  
class Module_Toolbar extends Module {
  
  var $rh;
  var $template = 'toolbar.html';
  var $data; //���� �� ��, ������ ������ DBDataView
  
  //������������ ����� ����������� ��� ������ ������������
  function RenderUserPart( $template ){
    $rh =& $this->rh;
    $prp =& $rh->prp;
    $tpl =& $rh->tpl;
    
    //���� ������������
    if( $prp->IsAuth() ){
      //��������� ������������
      $tpl->Assign(array(
        'login'=>$prp->user['login'],
        'role'=>$prp->ROLES[ $prp->user['role_id'] ],
        'href'=>$rh->url.'login?logout=1',
      ));
      $tpl->Parse( $template.':auth', 'user_part' );
    }else{
      //����� �����������
      $tpl->Assign('POST_STATE',$state->State(1));
      $tpl->Parse( $template.':not_auth', 'user_part' );
    }
  }
  
  function Load(){
    if( isset($this->data) ) return;
    //������ ����
    $this->rh->UseClass('DBDataView');
    $this->data =& new DBDataView( $this->rh, $this->rh->project_name.'_toolbar', array('id','title','href','main'), '_state=0', '_order ASC');
    $this->data->Load();
  }
  
  function RenderMenuItems( $template, $main_only=false ){
    $rh =& $this->rh;
    
    //��������� �� �������
    $ITEMS = array();
    $prp =& $rh->prp;
    $A =& $this->data->ITEMS;
    $n = count($A);
    for($i=0;$i<$n;$i++)
      if( $prp->IsGrantedTo($A[$i]['href']) && (!$main_only || $A[$i]['main'] ) )
        $ITEMS[] = $A[$i];
    
    //������������ ����
    $rh->UseClass("ListObject");
    $list =& new ListObject( $rh, $ITEMS );
    $list->implode = true;
    $list->issel_function = '_toolbar_current_checker';
    $list->Parse( $template, '__list' );
    
  }
  
  //������������ ���� ��� �������� ������
  function RenderMenuPart( $template ){
    $rh =& $this->rh;
    
    $db = $rh->db;
    $tpl =& $rh->tpl;
    
    $this->Load();
    
    $this->RenderMenuItems( $template.':List', $rh->toolbar_main_only );
    
    //������
    $tpl->Parse( $template, 'menu_part' );
    $tpl->Free('__list');
  }
  
  function Handle(){
    $rh =& $this->rh;
    
    //� ���������� ��������� ������ ���������� �� �����
    if( $rh->GetVar('hide_toolbar') ){
      $rh->state->Set('hide_toolbar',1);
      return;
    }
    
    //���� ������
    if( $this->ShowToolbar() ){
      //������ ��� ������
      $rh->tpl->assign( '_/', $rh->back_end->path_rel ? $rh->back_end->path_rel : $rh->url );
      //������������ �����
      $this->RenderUserPart( $this->template );
      $this->RenderMenuPart( $this->template.':menu' );
      //������ ������
      $rh->tpl->Parse( $this->template.':toolbar', 'toolbar' );
    }
  }
  
  //���������� ��� ��� ������?
  function ShowToolbar(){
    $rh =& $this->rh;
    $prp =& $rh->prp;
    return $rh->show_toolbar || in_array( $prp->user['role_id'], $this->rh->prp->ADMIN_ROLES );
  }
  
}

function _toolbar_current_checker( &$list ){
  $rh =& $list->rh;
  $item =& $list->DATA[ $list->loop_index ];
  return substr( $rh->url_rel, 0, strlen($item['href']) ) == $item['href'] ? '_sel' : '';
}

?>