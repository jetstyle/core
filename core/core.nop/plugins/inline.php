<?php
  /*
  
  Inline
  -------
  
  ��������� � ������ �������� ������� � �����.
  
  � ���������� ������ �������� � ���� <HEAD> ��������� ����:
    
    <script type="text/javascript" language="Javascript" >{{_}}</script>
    
    ���
    
    <style>
    {{_}}
    </style>
    
  ��� ������� �������������:
  
  1. {{!!inline}}...{{/!!}}
      - ���������� ���� ���� ��� ����������� ������� � ���� <HEAD>

  1a. {{!!inline onload}}...{{/!!}}
      - ���������� ���� ���� ��� ������� � body::onLoad (��.����)
        
  
  2. {{!inline inline.html:Css}}
      - ������ ��������� ������ � ���������� ��������� ��� ������� � <HEAD>. 
  
  3. {{!inline compile=head|onload}}
      compile=head - ���������� ����� ������� � <HEAD> ��������
      compile=onload - ���������� ������� � body::onLoad <- � ��� ������� � ��� �������???
  
  -------
  
  $params:
    0 - ��� ������� ��� ��������
    "compile" - ���� ����������
    "_" - ����� ��� �������
  
  ������ ������ � $tpl:
  $tpl->INLINE = array(
      'head'=>array('',...),
      'onload'=>array('',...),
    );
  
  */

  $str = "";
  
  $compile = $params['compile'];
  
  if ( $compile )
  {
    //����������� �����������
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
  else //����������� ��� ����������
  {
    //onload?
    if($params[0]=='onload' && $params['_']!='' )
    {
      $tpl->INLINE['onload'][] = 
        preg_replace("/\n|\r/","",
        str_replace('"','\'',$params['_']));
      return;
    }
    
    //���� ������ ��� ��������?
    if($params[0])
      $tpl->INLINE['head'][] = $tpl->Parse($params[0]);
    
    //������� ��� ����?
    if( $params['_']!='' )
      $tpl->INLINE['head'][] = 
        //�������� ���������� ������ �������� �� �����������
        str_replace( "<script>", '<script type="text/javascript" language="Javascript" >', $params['_'] );
    
  }
  
?>