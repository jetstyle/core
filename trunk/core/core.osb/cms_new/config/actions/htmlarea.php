<?
  
function action_htmlarea( &$rh, $PARAMS ){
  
  $tpl =& $rh->tpl;

  extract($PARAMS);
  if(!$template) $template = 'forms/htmlarea.html';
  if(!isset($plugins)) $plugins = 'TableOperations,ContextMenu';
  
  //добавляем относительные пути к Сайту в вызовы стилей
  //Собираем содержимое стилей, которое потом выкусим
  if(!$styles) $styles = $rh->htmlarea->style_sheets;
  if( $rh->front_end->path_rel && $styles ){
    $A = explode(',',$styles);
    $styles = "";
//    $styles = $rh->front_end->path_rel.implode( ','.$rh->front_end->path_rel, $A );
    foreach($A as $_style)
//      $_styles .= implode('',file($_SERVER["DOCUMENT_ROOT"].$rh->front_end->path_rel.$_style));
      $styles .= "@import url('".$rh->front_end->path_rel.$_style."'); ";
  }
  
  $tpl->Assign(array(
    "__input_name"=> $tpl->GetAssigned( $tpl_prefix ) . $input_name,
    "__cols"=> $cols ? $cols : 70,
    "__rows"=> $rows ? $rows : 20,
    "__text"=> trim($__string) ? $__string : '<p>&nbsp;</p>',
    "__config_name"=> $config_name ? $config_name : '_editor_config',
    "__plugins"=> $plugins,
    "__style"=> $style,
    "__styles"=> $styles,
//    "__styles_inline"=> $_styles,
    "__base_url"=> $rh->front_end->path_rel ? 'http://'.$rh->host_name.'/'  : $tpl->GetAssigned('/'),
    '__body_class'=> $body_class ? $body_class : $rh->htmlarea->body_class,
  ));
  
  //инициализация движка HTMLArea
  if( !$rh->__html_area_init ){
    $rh->__html_area_init = true;
    $tpl->Parse( $template.':init', 'html_head', true);
    $tpl->Parse( $template.':onload', 'html_onload', true);
  }
  
  //рендерим инстанс
  $tpl->Parse( $template.':instance', 'html_head', true);
  
  //указан список плагинов - рендерим вызов в html_head
  if( $plugins!='none' ){
    $template_plugin = $template.':plugin';
    $A = explode(',',$plugins);
    $n = count($A);   
    for($i=0;$i<$n;$i++)
      if( !$rh->__htmlarea_plugins[$A[$i]] ){
        $rh->__htmlarea_plugins[$A[$i]] = true;
        $tpl->Assign('__plugin_name',$A[$i]);
        $tpl->parse($template_plugin,'html_head',true);
      }
  }
  
  return $tpl->Parse( $template.':textarea' );
}
  
?>