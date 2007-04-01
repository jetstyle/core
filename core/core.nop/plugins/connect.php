<?php
  /*
  
  Connect
  -------
  
  ���������� � ������ �������� ����� �� ������� ������� - js � css.
  
  � ���������� ������ �������� � ���� <HEAD> ��������� ����:
    <script type="text/javascript" language="Javascript" >{{_}}</script>
    <script type="text/javascript" language="Javascript" src="{{js}}{{_}}.js"></script>
  
  ��� ������� �������������:
  
  1. {{!connect news.css}} ��� {{!connect news.js}}
      - ����������, ��� ������ ����� ����� ������������ � <HEAD> ��������.
  
      - ��� ��������: 
        {{!connect news.css path="custompath"}} 
        {{!connect news.css lib="wikiedit"}} 
  
  2. {{!connect compile=css}} ��� {{!connect compile=js}}
      - ���������� ����� ��������������� ������ ��� <HEAD>, ��� ���� �������� ����������� ���������.
  
  -------
  
  $params:
    0 - ��� ����� ��� �����������, ���� �������������� ������� � {{js}} ��� {{css}}
    "compile" - ���� ����������
  
  ������ ������ � $tpl:
  $tpl->CONNECT = array(
      "js"=>array("",...),
      "css"=>array("",...),
    );
  
  */

  $str = "";
  
  $compile = isset($params["compile"]) ? $params["compile"] : false;

  if ( $compile )
  {
    //����������� �����������
    if ( isset($tpl->CONNECT[$compile]) && is_array($tpl->CONNECT[$compile]) )
    {
      $template = "_/connect.html:".$compile;
      foreach( $tpl->CONNECT[$compile] as $fname )
      {
        if (!is_array( $fname )) // ������ ���� � ������� �����
        {
          $tpl->set("_",$fname);
        $str .= $tpl->parse($template);
      }
        else // ���� � ������������ ����
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
    //����������� ��� ����������
    $A = explode(".",$params[0]);
    $ext = array_pop($A);
    $fname = implode(".",$A);

    if( !isset($tpl->CONNECT[$ext]) || !is_array($tpl->CONNECT[$ext]) || !in_array($fname,$tpl->CONNECT[$ext]) )
    {
      if (isset($params["lib"])) // ���� ���� ��������� � ����
        $params["path"] = $rh->lib_href_part."/".$params["lib"];

      if (!isset($params["path"])) // ������ ���� � ������� �����
      $tpl->CONNECT[$ext][] = $fname;
      else // ���� � ������������ ����
        $tpl->CONNECT[$ext][] = array( "file" => $fname, "path" => rtrim($ri->Href($params["path"]),"/") );
    }
  }
  
?>
