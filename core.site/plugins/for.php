<?php

/**
 * {{!for news do=test.html:news}}
 * {{!for news as news_item do=test.html:news}}
 */	    

	$data = &$params[0];

	if((is_array($data) || ($data instanceof ArrayAccess)) && count($data) > 0)
	{
		$content = '';
		
		$templateName = substr($params['do'], 1);
	 	$storeTo = $params['as'] ? $params['as'] : '*';
	 	$params[$storeTo] = &$params[0];
	 	$sep = ($params['sep'] && $params['sep']{0} == '@') ? $tpl->parse(substr($params['sep'], 1)) : "";
	 	$params['for'] = '';
	 	
	 	unset($params[0], $params['as'], $params['do'], $params['sep'], $params['_name']);
 	
		$stackId = $tpl->addToStack($params);
	 	$i=0;
	 	$total = count($data);
		foreach($data AS $key => $r)
		{
			$for = array(
				'i' => ++$i,
				'key' => $key,
				'odd' => $i%2,				
			);
			$for['even'] = !$for['odd'];
			
			if ($i == 1)
			{
				$for['first'] = true;
			}
			
			if ($i == $total)
			{
				$for['last'] = true;
			}
			
			$tpl->setRef('for', $for);
			$tpl->setRef($storeTo, $r);
			$content .= ($content ? $sep : '').$tpl->parse($templateName);
		}
	
		$tpl->freeStack($stackId);
		
		echo $content;	
	}
?>