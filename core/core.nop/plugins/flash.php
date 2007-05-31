<?php
    /*
    SWF magic     {{!flash someflash.sfw}}
    {{!flash shapka1.swf transparent=1 height="670" version=8 instant=1}}
    -----------
    
    Вставляет флэш с помощью swfobject.js
    
    Параметры:
      [0]         -- path/filename.swf единственный обязательный параметр
      transparent -- по умолчанию "false"
      width       -- по умолчания 100%
      height      -- 
      scale       -- по умолчанию "noscale"
      id          -- по умолчанию берётся имя файла без расширения
      container   -- куда класть флэш. По умолчанию он генерится
                     автоматом как <div id="{{id}}_container">,
                     и туда флэш и кладётся.

    */
       
    // подцепить шаблон генерации вставки флэша,
    // где уже будет подцепляться swfobject.js
    
    // сгенерить из шаблона скрипт вставки флэша

    foreach ( array_keys($params) as $k ){    
       $params[$k] = $tpl->ParseInstant(
                       str_replace(']]','}}',
                          str_replace('[[','{{',$params[$k])) );
    }

    $src = $params['src'] = $params['src']?$params['src']:$params[0];
    unset($params[0]);
        
    if (!$src) return;
    
    //отрезать параметры от имени
    list($filename,$query) = explode("?",$src);
    
    $id = basename ($filename,".swf");
    if ( !$container = $params['container'] ){
      $container = $id.'_container';
      $generate_container = true;
    }
    
    $defaults = array(
      'src'   => '',
      'width'   => "100%",
      'height'  => "100",
      'id' => $id,
      'container' => $id.'_container',
      'generate_container' => $generate_container,
      'version' => "7",
      'scale'   => "noscale",
      'salign'  => "l",
      'bgcolor' => "#ffffff",
      'quality' => "high",
      'menu'    => "false",
      'xiRedirectUrl' => "",
      'redirectUrl'   => "",
      'detectKey'     => "",
      'transparent'   => "",
      );

    $ignores = array( 'instant' );
    foreach( $ignores as $i){
      unset($params[$i]);
    }   
    foreach( $params as $k=>$v){
      if ($k[0]=="_")
        unset($params[$k]);
    }   

    foreach( $defaults as $k=>$v){
      $tpl->set( $k, $params[$k]?$params[$k]:$v );
      //echo ($k.'='.($params[$k]?$params[$k]:$v).'<br/ >');
    }   

    // остальное -- flashvars
    $flashvar_keys = array_diff(array_keys($params),array_keys($defaults));

    foreach ($flashvar_keys as $k){
      $tpl->set( 'var_key', $k );
      $tpl->set( 'var_value', $params[$k] );
      $tpl->Parse("_/swfobject.html:Variable_Item",'flashvars',1);
    }
    
    echo $tpl->Parse("_/swfobject.html:body");

?>
