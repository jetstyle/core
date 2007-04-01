<?php
  /*
  
  Connect
  -------
  
  Подключает в начало страницы линки на внешние ресурсы - js и css.
  
  В результате работы получаем в тэге <HEAD> вхождения типа:
    <script type="text/javascript" language="Javascript" >{{_}}</script>
    <script type="text/javascript" language="Javascript" src="{{js}}{{_}}.js"></script>
  
  Два вариант использования:
  
  1. {{!connect news.css}} или {{!connect news.js}}
      - запоминает, что данные файлы нужно прилинковать в <HEAD> страницы.
  
      - ещё варианты: 
        {{!connect news.css path="custompath"}} 
        {{!connect news.css lib="wikiedit"}} 
  
  2. {{!connect compile=css}} или {{!connect compile=js}}
      - генерирует набор соответствующий линков для <HEAD>, при этом избегает дублирующих вхождений.
  
  -------
  
  $params:
    0 - имя файла для прилинковки, файл предполагается лежащим в {{js}} или {{css}}
    "compile" - флаг компиляции
  
  Хранит данные в $tpl:
  $tpl->CONNECT = array(
      "js"=>array("",...),
      "css"=>array("",...),
    );
  
  */

  $str = "";
  
  $compile = isset($params["compile"]) ? $params["compile"] : false;

  if ( $compile )
  {
    //компилируем накопленное
    if ( isset($tpl->CONNECT[$compile]) && is_array($tpl->CONNECT[$compile]) )
    {
      $template = "_/connect.html:".$compile;
      foreach( $tpl->CONNECT[$compile] as $fname )
      {
        if (!is_array( $fname )) // просто файл в текущей шкуре
        {
          $tpl->set("_",$fname);
        $str .= $tpl->parse($template);
      }
        else // файл с произвольным путём
        {
          $tpl->set("*",$fname);
          $str .= $tpl->parse($template."_path");
        }
      }
    }
    echo $str;
  }
  else
  {
    //накапливаем для компиляции
    $A = explode(".",$params[0]);
    $ext = array_pop($A);
    $fname = implode(".",$A);

    if( !isset($tpl->CONNECT[$ext]) || !is_array($tpl->CONNECT[$ext]) || !in_array($fname,$tpl->CONNECT[$ext]) )
    {
      if (isset($params["lib"])) // если файл находится в либе
        $params["path"] = $rh->lib_href_part."/".$params["lib"];

      if (!isset($params["path"])) // просто файл в текущей шкуре
      $tpl->CONNECT[$ext][] = $fname;
      else // файл с произвольным путём
        $tpl->CONNECT[$ext][] = array( "file" => $fname, "path" => rtrim($ri->Href($params["path"]),"/") );
    }
  }
  
?>
