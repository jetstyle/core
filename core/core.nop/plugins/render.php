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
   
	$stack = array(); // lucky: context saving storage

   foreach( $params as $key => $v){
     if ($v[0]=='@'){     

       $subtemplate = substr( $v, 1 );
       
       // ����� �������� ����������� �������� ��� �������� �������
       // � ������� caller, ���� ���������� ��������� ������
       if ($subtemplate[0]==':')
         $subtemplate = $caller.'.html'.$subtemplate;
		 $stack[$key] =& $rh->tpl->get($key);
  	   $rh->tpl->Set($key,$rh->tpl->parse($subtemplate));
	  }
	  // lucky: HACK set objects.. check param types in future
	  elseif (is_array($v) || is_object($v))
	  {
			 $stack[$key] =& $rh->tpl->get($key);
			$rh->tpl->SetRef($key, $v );
	  }
	  else
	  {
  	   // ���� � ��� � ���������� ������������ ���������� �����������,
  	   // �������� [[images]]
  	   $v = str_replace('[[','{{',$v);
  	   $v = str_replace(']]','}}',$v);
		$stack[$key] =& $rh->tpl->get($key);
  	   $rh->tpl->Set($key,$rh->tpl->ParseInstant( $v ));
  	 }
	 }
	 
	 //echo( $template );
	 
	 echo $rh->tpl->parse($template);

	 // lucky: HACK: restore context
	 foreach ($stack as $k=>$v) $rh->tpl->Set($key, $v );
	 unset($stack);
?>
