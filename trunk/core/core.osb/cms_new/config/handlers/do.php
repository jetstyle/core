<?
  include( $rh->FindScript('handlers','_start') );
  include( $rh->FindScript("handlers","_toolbar") );

  //не авторизован?
  if( !$prp->IsAuth() )
    $rh->redirect( $rh->url.'login' );
  
  //стартовая страница
  $module_name = $rh->state->Keep("module");
  
  if(!$module_name)
    $rh->redirect( $rh->url.'start' );
  
  //проверяем доступ к этому хэндлеру.
  //!!! Не нужно - в модулях есть отдельная проверка.
  /*
  if( !$prp->IsGrantedTo('do') ){
    echo $tpl->Parse('access_denied.html');
    $rh->End();
  }
  */
  
  //храним сквозные настройки
  $state->Keep('popup','integer');
  $state->keep("p","integer");
  
  //первый конфиг
  $rh->UseClass("ModuleConfig");
  $config =& new ModuleConfig( $rh, $rh->state->Keep("module") );
  $config->Read("defs");
  $config->Read( $mode=$rh->state->Keep("mode") );
  //основной модуль
  $module =& $config->InitModule();
  $module->store_to = "html_body";
  $module->Handle();
  //одна форма? оборачиваем в popup.html
  if( $mode="form" )
    $tpl->parse( "popup.html", "html_body" );
  
//  echo $tpl->Assigned( $module->store_to );
  
  $tpl->Assign('page_title',$config->module_title);
  
  include( $rh->FindScript('handlers','_finish') );
  
?>