<?php

/*
{{!render columns.html some="some" left=@left.html  right=@right.html}}
*/

   $template = $params[0];
   // чтобы работал caller, надо пропатчить шаблонный движок
   $caller = $params['_caller'];
   unset($params[0]);
   unset($params['_name']);
   unset($params['_plain']);
   unset($params['_caller']);
   
   foreach( $params as $key => $v){
     if ($v[0]=='@'){     

       $subtemplate = substr( $v, 1 );
       
       // чтобы работала возможность опускать имя текущего шаблона
       // с помощью caller, надо пропатчить шаблонный движок
       if ($subtemplate[0]==':')
         $subtemplate = $caller.'.html'.$subtemplate;
  	   $rh->tpl->Set($key,$rh->tpl->parse($subtemplate));
  	 }else{
  	   // если у нас в параметрах присутствуют переменные подстановок,
  	   // например [[images]]
  	   $v = str_replace('[[','{{',$v);
  	   $v = str_replace(']]','}}',$v);
  	   $rh->tpl->Set($key,$rh->tpl->ParseInstant( $v ));
  	 }
	 }
	 
	 //echo( $template );
	 
	 echo $rh->tpl->parse($template);

?>