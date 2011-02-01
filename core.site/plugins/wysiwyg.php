<?php
	
	$text = $params["_"] ? $params["_"] : $params[0];
	
	unset($params["_"]);
	
	$params['editor'] = 'wysiwyg' ;
	/*
	$controller = Locator::get('controller');
	
	if ( !isset($params['module']) )
	{
	    if ( in_array( $controller['controller'], array('home', 'content', '' ) ) )
	    {
		 $params['module']='content';
		 $params['id']= $controller['id'];
	    }
	    else
	    {
		$params['module']="texts";
	    }
	}
	*/
	
	return $text . Locator::get('tpl')->action( "inplace", $params );
?>
