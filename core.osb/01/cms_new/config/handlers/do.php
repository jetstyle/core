<?
  include( $rh->FindScript('handlers','_start') );
  include( $rh->FindScript("handlers","_toolbar") );

  //�� �����������?
  if( !$prp->IsAuth() )
    $rh->redirect( $rh->url.'login' );
  
  //��������� ��������
  $module_name = $rh->state->Keep("module");
  
  if(!$module_name)
    $rh->redirect( $rh->url.'start' );
  
  //��������� ������ � ����� ��������.
  //!!! �� ����� - � ������� ���� ��������� ��������.
  /*
  if( !$prp->IsGrantedTo('do') ){
    echo $tpl->Parse('access_denied.html');
    $rh->End();
  }
  */
  
  //������ �������� ���������
  $state->Keep('popup','integer');
  $state->keep("p","integer");
  
  //������ ������
  $rh->UseClass("ModuleConfig");
  $config =& new ModuleConfig( $rh, $rh->state->Keep("module") );
  $config->Read("defs");
  $config->Read( $mode=$rh->state->Keep("mode") );
  //�������� ������
  $module =& $config->InitModule();
  $module->store_to = "html_body";
  $module->Handle();
  //���� �����? ����������� � popup.html
  if( $mode="form" )
    $tpl->parse( "popup.html", "html_body" );
  
//  echo $tpl->Assigned( $module->store_to );
  
  $tpl->Assign('page_title',$config->module_title);
  
  include( $rh->FindScript('handlers','_finish') );
  
?>