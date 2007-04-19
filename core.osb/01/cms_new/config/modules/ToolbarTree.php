<?
/*
  ToolbarTree - ����������� ������ ������������ � ���� ������. ������ ��� ���.
  -------
  ��������������, ��� �����-�� ��������� ��� ��������.
*/
  
/*
CREATE TABLE [project_name]_toolbar (
  id int(11) NOT NULL auto_increment,
  title varchar(255) NOT NULL default '',
  href varchar(255) NOT NULL default '',
  main tinyint(4) NOT NULL default '0',
  _state tinyint(1) NOT NULL default '0',
  _modified timestamp(14) NOT NULL,
  _created timestamp(14) NOT NULL,
  _order int(11) NOT NULL default '0',
  _parent int(11) NOT NULL default '0',
  _level int(11) NOT NULL default '0',
  _left int(11) NOT NULL default '0',
  _right int(11) NOT NULL default '0',
  PRIMARY KEY (id),
  KEY _state(_state),
  KEY _order(_order),
  KEY main(main),
  KEY _parent(_parent,_level,_left,_right)
) TYPE=MyISAM;*/
  
class Module_ToolbarTree extends Module {
  
  var $rh;
  var $template = 'toolbar_tree.html';
  var $data; //���� �� ��, ������ ������ DBDataView
  var $root_id = 0; //������� ��������, ������������ � Handle()
  
  function Load(){
    if( isset($this->data) ) return;
    //������ ����
    $this->rh->UseClass('DBDataView');
    $this->data =& new DBDataView( $this->rh, $this->rh->project_name.'_toolbar', array('id','title','href','main','_parent'), '_state=0', '_order ASC');
    $this->data->result_mode = 2;
    $this->data->Load();
    //��������� �����������
    foreach($this->data->ITEMS as $id=>$r)
    {
      $this->data->ITEMS[$id]["granted"] = $this->rh->prp->IsGrantedTo($r['href']);
      $granted_ids[] = $id;
    }
    
    //������� ���� � ������� ��� ������� ����� - ������
    foreach ($granted_ids as $id)
    {
        //if ($this->data->ITEMS[$id]['granted'])
       // echo '<br>'.$id.'  '.$this->data->ITEMS[$id]['title'].' ';
        //� ������� ���� ���� ����
        if (!empty($this->data->CHILDREN[$id]))
        {
            $no_sub_grants = true;
            foreach ($this->data->CHILDREN[$id] as $children_id)
            {
                if ($this->data->ITEMS[$children_id]['granted']==true && $no_sub_grants == true)
                    $no_sub_grants = false;
            }
            
            if ($no_sub_grants)
            {
                $this->data->ITEMS[$id]['granted'] = false;
            }
        }
    }
  }
  
  //�������� ��������
  function MarkOpen()
  {
    $rh =& $this->rh;
    //���� ��, �� ��� ������ ������
    $id = 0;
    foreach($this->data->ITEMS as $_id=>$r){
      if( 
        $r["href"]!="" && 
        !is_array($this->data->CHILDREN[$_id]) && 
        substr( $rh->url_rel, 0, strlen($r['href']) ) == $r['href'] 
        ){
          $id = $_id;
          break;
      }
    }
    //��������� ������ 1�� ������ �� ��������
    if($id){
      while($this->data->ITEMS[$id]["_parent"]){
        $this->data->ITEMS[$id]["opened"] = true;
        $id = $this->data->ITEMS[$id]["_parent"];
      }
      $this->rh->tpl->assign("opened_id",$id);
    }
  }
  
  function RenderMenuLevel1( $template, $store_to ){
    $rh =& $this->rh;
    
    //��������� ������
    $ITEMS = array();
    if(is_array($this->data->CHILDREN[0])){
      foreach($this->data->CHILDREN[0] as $id){
        $r = $this->data->ITEMS[$id];
        if( $r["granted"] ){  
          $r["_suffix"] = $r["id"] == $this->root_id ? "_sel" : "";
          $r["onclick"] = is_array( $this->data->CHILDREN[ $r["id"] ]) ? "SwitchBar('tb_".$r["id"]."'); return false;" : "";
          $ITEMS[] = $r;
        }
      }
    }
    
    //������
    $list =& new ListObject( $rh, $ITEMS );
    $list->implode = true;
    $list->Parse( $template.":List", '__list' );
    
    //������
    $rh->tpl->parse( $template, $store_to );
  }
  
  function RenderMenuLevel2( $template, $store_to ){
    $rh =& $this->rh;
    
    //��� �����������? �������� �������
    if( !$this->root_id || !is_array($this->data->CHILDREN[$this->root_id]) ){
      $rh->tpl->assign( $store_to, "" );
      return;
    }
    
    //��������� ������
    $ITEMS = array();
    foreach($this->data->CHILDREN[$this->root_id] as $id){
      $r = $this->data->ITEMS[$id];
      if( $r["granted"] ){  
        $r["_suffix"] = $r["opened"] ? "_sel" : "";
        $ITEMS[] = $r;
      }
    }
    
    //������
    $list =& new ListObject( $rh, $ITEMS );
    $list->implode = true;
    $list->Parse( $template.":List", '__list' );
    
    //������
    $rh->tpl->parse( $template, $store_to );
  }
  
  //������������ ���� ��� �������� ������
  function RenderMenuPart( $template ){
    $this->RenderMenuLevel1( $template.':menu', "menu_part" );
    $this->RenderMenuLevel2( $template.':submenu', "submenu_part" );
  }
  
  function Handle(){
    $rh =& $this->rh;
    
    //� ���������� ��������� ������ ���������� �� �����
    if( $rh->GetVar('hide_toolbar') ){
      $rh->state->Set('hide_toolbar',1);
      return;
    }
    
    //���� ������
    if( $this->ShowToolbar() )
    {
      
      $rh->UseClass("ListObject");
      
      //������ ��� ������
      $rh->tpl->assign( '_/', $rh->back_end->path_rel ? $rh->back_end->path_rel : $rh->url );
      $rh->tpl->assign( 'opened_id', '0' );
      
      //������ � �����
      $this->Load();
      $this->MarkOpen();
      
      //������������ �����
      $this->RenderUserPart( $this->template );
      
      //������������ ��� �������� ������
      
      //��� ��������
      $this->RenderMenuPart( $this->template );
      $rh->tpl->Assign("tb_id","tb_0");
      if ($rh->GetVar('lang') == 'en')
      {
        $rh->tpl->Assign ('lang_title', 'Change to RU');
        $rh->tpl->Assign ('lang', 'ru');
      }
      else
      {
        $rh->tpl->Assign ('lang_title', '����������� �� EN');
        $rh->tpl->Assign ('lang', 'en');
      }
      
      $rh->tpl->Parse( $this->template.':toolbar', '_toolbars', true );
      
      //��������
      if(is_array($this->data->CHILDREN[0])){
        foreach($this->data->CHILDREN[0] as $root_id){
          $this->root_id = $root_id;
          $this->RenderMenuPart( $this->template );
          $rh->tpl->Assign("tb_id","tb_".$root_id);
          $rh->tpl->Parse( $this->template.':toolbar', '_toolbars', true );
        }
      }
      
      //������ ��� ���� ������
      $rh->tpl->Parse( $this->template.':toolbar_wrapper', 'toolbar' );
    }
  }
  
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
  
  //���������� ��� ��� ������?
  function ShowToolbar()
  {      
    $rh =& $this->rh;
    $prp =& $rh->prp;
    return $rh->show_toolbar || in_array( $prp->getUserRole(), $this->rh->prp->ADMIN_ROLES );
  }
}

?>