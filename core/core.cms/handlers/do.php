<?php
  include( $rh->findScript('handlers','_start') );
  include( $rh->findScript("handlers","_toolbar") );

  //�� �����������?
  if( !$prp->IsAuth() )
  {
    $rh->redirect( $rh->url.'login?ret_url='.$rh->getUrl() );
  }

  if(!($moduleName = $rh->state->keep("module")))
  {
    $rh->redirect( $rh->url.'start' );
  }

  //������ �������� ���������
  $state->keep('popup','integer');
  $state->keep("p","integer");

  //������ ������
  $rh->useClass("ModuleConfig");
  $config =& new ModuleConfig( $rh, $moduleName );

  $config->Read("defs");
  $config->Read( $mode=$rh->state->Keep("mode") );
  //�������� ������
  $module =& $config->InitModule();
  $module->store_to = "html_body";
  $module->Handle();


  $tpl->set('page_title',$config->module_title);

  $tpl->parse("layouts/inner.html","html_body");

  include( $rh->FindScript('handlers','_finish') );

?>