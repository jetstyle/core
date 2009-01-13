<?php
  /*
  
  Inline
  -------
  
  Вписывает в начало страницы скрипти и стили.
  
  В результате работы получаем в тэге <HEAD> вхождения типа:
    
    <script type="text/javascript" language="Javascript" >{{_}}</script>
    
    или
    
    <style>
    {{_}}
    </style>
    
  Три вариант использования:
  
  1. {{!!inline}}...{{/!!}}
      - запоминает весь блок для последующей вставки в блок <HEAD>

  1a. {{!!inline onload}}...{{/!!}}
      - запоминает весь блок для вставки в body::onLoad (см.ниже)
        
  
  2. {{!inline inline.html:Css}}
      - парсит указанный шаблон и запоминает результат для вставки в <HEAD>. 
  
  3. {{!inline compile=head|onload}}
      compile=head - генерирует набор вставок в <HEAD> страницы
      compile=onload - генерирует вставку в body::onLoad <- а как парсить в эту область???
  
  -------
  
  $params:
    0 - имя шаблона для парсинга
    "compile" - флаг компиляции
    "_" - текст для вставки
  
  Хранит данные в $tpl:
  $tpl->INLINE = array(
      'head'=>array('',...),
      'onload'=>array('',...),
    );
  
  */

  $str = "";
  
  $compile = $params['compile'];
  
  if ( $compile )
  {
    //компилируем накопленное
    if( $compile=='onload' )
    {
      //body.onLoad
      if(is_array($tpl->INLINE['onload']))
        $str = implode(';',$tpl->INLINE['onload']);
    }
    else
    {
      //HTML::HEAD
      if( isset($tpl->INLINE[$compile]) && is_array($tpl->INLINE[$compile]) )
        $str = implode("\n",$tpl->INLINE[$compile]);
    }
    echo $str;
    
  }
  else //накапливаем для компиляции
  {
    //onload?
    if($params[0]=='onload' && $params['_']!='' )
    {
      $tpl->INLINE['onload'][] = 
        preg_replace("/\n|\r/","",
        str_replace('"','\'',$params['_']));
      return;
    }
    
    //дали шаблон для парсинга?
    if($params[0])
      $tpl->INLINE['head'][] = $tpl->Parse($params[0]);
    
    //вызвали как блок?
    if( $params['_']!='' )
      $tpl->INLINE['head'][] = 
        //заменяем упрощённую обёртку скриптов на полноценную
        str_replace( "<script>", '<script type="text/javascript" language="Javascript" >', $params['_'] );
    
  }
  
?>