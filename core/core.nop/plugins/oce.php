<?php

	if(      $rh->principal->Security("noguests") 
/*
			$rh->toolbar && 
			$rh->toolbar->ShowToolbar() && 
			($_COOKIE["oce"]=="on" || $_GET["oce"]=="on") && 
			$_GET["oce"]!="off" 
        */
	)
    {

  	//$tpl =& $rh->tpl;

  	$module = $params['module'];
  	$var = $params['var'];
  	$id = (integer)$params['id'];
    
		if( !isset($rh->OCE[$module]) )
			$rh->debug->Error("OCE: module not found, module=$module, id=$id, var=$var");
  	
  	if($var)
  		$id = (integer)$tpl->GetValue($var);
    
		if( !$id )
			$rh->debug->Error("OCE: id not found, module=$module, id=$id, var=$var");
  	
  	$tpl->set('_module',$module);		
  	$tpl->set('_id',$id);		
    //echo ('cms_url='.$rh->cms_url);
  	$tpl->set('_href', ( ($rh->cms_url[0]!="/"&&(strpos($rh->cms_url, "http://") !== 0)) ? "/" : "" ).$rh->cms_url.str_replace('::id::',$id,$rh->OCE[$module]).'hide_toolbar=1&popup=1' );
  	$tpl->set('_width', $params['width'] ? $params['width'] : 300 );		
  	$tpl->set('_height', $params['height'] ? $params['height'] : 400 );		
  	$tpl->set('_title', $params['title'] ? $params['title'] : 'редактировать' );		
    
  	return $tpl->parse('oce.html');
    
	}else
		return '';

?>
