<?php

  if(!$params['template']) $params['template'] = 'tinymce.html';

  if ($params['input_name']) 
  {
  	$r = array(
  		'name' => $params['tpl_prefix'] . $params['input_name'],
  		'value' => trim($params['_']) ? $params['_'] : '<p>&nbsp;</p>',
  	);
  	$tpl->setRef('f', $r);
  	
  	$names = $tpl->get('rich_editors');
  	$names = $names ? $names.','.$params['tpl_prefix'] . $params['input_name'] : $params['tpl_prefix'] . $params['input_name'];
  	$tpl->set('rich_editors', $names);
  	
    $out = $tpl->parse($params['template'].':instance');
  }

  if ($params['init'])
  {
      $out .= $tpl->parse($params['template'].":init");
  }
  
  if ($params['wrap'])
  {
      $wrap = array( "_", $out,
		     "title"=>$params['title'], 
                     "closed"=>( $params['wrap']=="closed" ? 1 : 0 ) );
      $out = $tpl->action("wrap", $params);
  }
  
  echo $out;
?>