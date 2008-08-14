<?php
//@oce.html
if (RequestInfo::get('oce') == 'off') return '';
if (!RequestInfo::get('oce') && $_COOKIE['oce'] != 'on') return '';

if(Locator::get('principal')->isAuth())
{
	$oce = Config::get('oce');
	$module = $params['module'];
	$id = (integer)$params['id'];

	if( !isset($oce[$module]) )
	{
		Debug::trace("<span style='color: red; font-weight: bold;'>OCE: module not found, module=$module, id=$id, var=$var</span>");
		return '';
	}
	
	if( !$id )
	{
		Debug::trace("<span style='color: red; font-weight: bold;'>OCE: id not found, module=$module, id=$id, var=$var</span>");
	}

	$tpl->set('_module',$module);
	$tpl->set('_id',$id);
	$tpl->set('_href', (Config::exists('cms_url') ? Config::get('cms_url') : RequestInfo::$baseUrl."cms/").str_replace('::id::',$id,$oce[$module]).'hide_toolbar=1&popup=1' );
	$tpl->set('_width', $params['width'] ? $params['width'] : 500 );
	$tpl->set('_height', $params['height'] ? $params['height'] : 600 );
	$tpl->set('_title', $params['title'] ? $params['title'] : 'редактировать' );

	return $tpl->parse('oce.html');
}
return '';
?>