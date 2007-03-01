<?
  $prp->Authorise();
  if( $rh->GetVar('logout') )
    $prp->Logout( $rh->logout_url ? $rh->logout_url : $_SERVER["HTTP_REFERER"] );
//    $prp->Logout('login');
  
  //уже авторизован?
  if( $prp->IsAuth() )
    $rh->redirect( $rh->url );
  
  //рисуем тулбар
//  if( $rh->render_toolbar ){
    $rh->toolbar =& $this->UseModule('Toolbar');
    $rh->toolbar->Handle();
//  }
  
  /* отрисуем форму */
  $template = 'login.html';
  $tpl->Assign( 'POST_STATE', $state->State(1) );
  if( $prp->IsAuth() )
    $tpl->Parse( $template.':logout', 'logout');
  $tpl->parse( $template, 'html_body');
  
  include( $rh->FindScript('handlers','_page_attrs') );
  $tpl->Assign('page_title','Авторизация');
  
  echo $tpl->Parse( 'html.html' );
  
  //исключительно в отладочных целях
//  $debug->Trace_R($prp,0,'Principal');
  
?>