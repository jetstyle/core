<?php
  include( $rh->findScript('handlers','_start') );
  include( $rh->findScript("handlers","_toolbar") );

  //не авторизован?
  if( !$prp->IsAuth() )
  {
    $rh->redirect( $rh->url.'login' );
  }

  if(!($moduleName = $rh->state->keep("module")))
  {
    $rh->redirect( $rh->url.'start' );
  }
  
  //храним сквозные настройки
  $state->keep('popup','integer');
  $state->keep("p","integer");
  
  //первый конфиг
  $rh->useClass("ModuleConfig");
  $config =& new ModuleConfig( $rh, $moduleName );
  
  $config->Read("defs");
  $config->Read( $mode=$rh->state->Keep("mode") );
  //основной модуль
  $module =& $config->InitModule();
  $module->store_to = "html_body";
  $module->Handle();
  //одна форма? оборачиваем в popup.html
 /*
  if( $mode=="form" )
  {
    $tpl->parse( "popup.html", "html_body" );
  }
  */
//  echo $tpl->Assigned( $module->store_to );
  
  $tpl->set('page_title',$config->module_title);
  
  $tpl->parse("layouts/inner.html","html_body");   
  
  include( $rh->FindScript('handlers','_finish') );
  
?>