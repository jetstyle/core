<?php

/*
{{!render columns.html some="some" left=@left.html  right=@right.html}}
*/

   $template = $params[0];
   // ����� ������� caller, ���� ���������� ��������� ������
   $caller = $params['_caller'];
   unset($params[0]);
   unset($params['_name']);
   unset($params['_plain']);
   unset($params['_caller']);
   
   foreach( $params as $key => $v){
     if ($v[0]=='@'){     

       $subtemplate = substr( $v, 1 );
       
       // ����� �������� ����������� �������� ��� �������� �������
       // � ������� caller, ���� ���������� ��������� ������
       if ($subtemplate[0]==':')
         $subtemplate = $caller.'.html'.$subtemplate;
  	   $rh->tpl->Set($key,$rh->tpl->parse($subtemplate));
  	 }else{
  	   // ���� � ��� � ���������� ������������ ���������� �����������,
  	   // �������� [[images]]
  	   $v = str_replace('[[','{{',$v);
  	   $v = str_replace(']]','}}',$v);
  	   $rh->tpl->Set($key,$rh->tpl->ParseInstant( $v ));
  	 }
	 }
	 
	 //echo( $template );
	 
	 echo $rh->tpl->parse($template);

?>