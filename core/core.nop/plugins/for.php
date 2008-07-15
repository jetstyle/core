<?php

/**
 * {{!for news do=test.html:news}}
 * {{!for news as news_item do=test.html:news}}
 */	    

	$data = &$params[0];

	if((is_array($data) || ($data instanceof ArrayAccess)) && count($data) > 0)
	{
		$for = array();
		$content = '';
		
		$templateName = substr($params['do'], 1);
	 	$storeTo = $params['as'] ? $params['as'] : '*';
	 	$params[$storeTo] = &$params[0];
	 	$sep = ($params['sep'] && $params['sep']{0} == '@') ? $tpl->parse($params['sep']) : "";
	 	$params['for'] = '';
	 	
	 	unset($params[0], $params['as'], $params['do'], $params['sep'], $params['_name']);
 	
		$stackId = $tpl->addToStack($params);
	 	$i=0;
		foreach($data AS $key => $r)
		{
			$for['i'] = ++$i;
			$for['key'] = $key;
			
			$rh->tpl->setRef('for', $for);
			$rh->tpl->setRef($storeTo, $r);
			echo ($content ? $sep : '').$tpl->parse($templateName);
		}
	
		$tpl->freeStack($stackId);
		
//		echo $content;	
	}
?>