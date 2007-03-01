<?
  
  include( $rh->FindScript("handlers","_start") );
  
  //проверяем доступ к этому хэндлеру
  if( !$prp->IsGrantedTo('start') ){
    echo $tpl->Parse('access_denied.html');
    $rh->End();
  }
  
  include( $rh->FindScript("handlers","_toolbar") );
  
  //собираем все разделы для главной страницы
  $ITEMS = array();
  foreach( $rh->toolbar->data->ITEMS as $id=>$r )
    if(
      $r["granted"] && $r["main"] && 
      !is_array( $rh->toolbar->data->CHILDREN[ $r["id"] ] )
    )
      $ITEMS[] = $r;
  
  //рендерим
  $list =& new ListObject( $rh, $ITEMS );
  $list->parse( 'start.html:List', "__list" );
  
  $tpl->parse("start.html","html_body");    
  
  include( $rh->FindScript("handlers","_finish") );
  
?>