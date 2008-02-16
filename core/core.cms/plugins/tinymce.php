<?php

  if(!$params['template']) $params['template'] = 'tinymce.html';

  if ($params['input_name']) 
  {
  	$r = array(
  		'name' => $params['tpl_prefix'] . $params['input_name'],
  		'value' => trim($params['_']) ? $params['_'] : '<p>&nbsp;</p>',
  	);
  	$rh->tpl->setRef('f', $r);
  	
  	$names = $rh->tpl->get('rich_editors');
  	$names = $names ? $names.','.$params['tpl_prefix'] . $params['input_name'] : $params['tpl_prefix'] . $params['input_name'];
  	$rh->tpl->set('rich_editors', $names);
  	
  	if($params['light'])
  	{
  		$names = $rh->tpl->get('light_editors');
  		$names = $names ? $names.',"'.$params['tpl_prefix'] . $params['input_name'] . '"' : '"' . $params['tpl_prefix'] . $params['input_name'] . '"';
  		$rh->tpl->set('light_editors', $names);
  	}
  	
    $out = $rh->tpl->parse($params['template'].':instance');
  }

  if ($params['init'])
  {
      $out .= $rh->tpl->parse($params['template'].":init");
  }
  
  echo $out;
?>