<?php
$rh->OCE = array(
		"texts"=>"do/Texts/form?id=::id::&",
		"news"=>"do/News/form?id=::id::&",
		"content"=>"do/Content/form?id=::id::&",
  );

	if($rh->principal->isAuth() && $rh->ri->get('oce') == 'on')
    {

  	//$tpl =& $rh->tpl;

  	$module = $params['module'];
  	$var = $params['var'];
  	$id = (integer)$params['id'];

		if( !isset($rh->OCE[$module]) )
		{
//			$rh->debug->Error("OCE: module not found, module=$module, id=$id, var=$var");
			Debug::trace("<span style='color: red; font-weight: bold;'>OCE: module not found, module=$module, id=$id, var=$var</span>");
			return '';
		}

  	if($var)
  		$id = (integer)$tpl->GetValue($var);

		if( !$id )
		{
			Debug::trace("<span style='color: red; font-weight: bold;'>OCE: id not found, module=$module, id=$id, var=$var</span>");
//			$rh->debug->Error("OCE: id not found, module=$module, id=$id, var=$var");
		}

  	$tpl->set('_module',$module);
  	$tpl->set('_id',$id);
    //echo ('cms_url='.$rh->cms_url);
  	$tpl->set('_href', ($rh->cms_url ? $rh->cms_url : "/cms/") .str_replace('::id::',$id,$rh->OCE[$module]).'hide_toolbar=1&popup=1' );
  	$tpl->set('_width', $params['width'] ? $params['width'] : 300 );
  	$tpl->set('_height', $params['height'] ? $params['height'] : 400 );
  	$tpl->set('_title', $params['title'] ? $params['title'] : 'редактировать' );

  	return $tpl->parse('oce.html');

	}else
		return '';

?>
