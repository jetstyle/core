<?php
//@oce.html

if (RequestInfo::get('oce') == 'off' && !$params['show'] ) return '';
if (!RequestInfo::get('oce') && $_COOKIE['oce'] != 'on' && !$params['show']) return '';

$module = $params['module'];

if(Locator::get('principalCms')->security('cmsModules', $module))
{
	$oce = Config::get('oce');
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

	$tpl->set('_module', $module);
	$tpl->set('_id', $id);
	$tpl->set('_href', (Config::exists('cms_url') ? Config::get('cms_url') : RequestInfo::$baseUrl."cms/").str_replace('::id::',$id,$oce[$module]).'hide_toolbar=1&popup=1' );
	$tpl->set('_width', $params['width'] ? $params['width'] : 500 );
	$tpl->set('_height', $params['height'] ? $params['height'] : 600 );
	$tpl->set('_title', $params['title'] ? $params['title'] : 'редактировать' );
	$tpl->set('_field', $params['field'] );
	$tpl->set('_parent', $params['container']=='parent' ? 1 : null );
	$tpl->set('_thickbox', isset($params['thickbox']) ? 1 : null );
	
	if ($params['inplace']=='wysiwyg')
	{
	    $tpl->set('wysiwyg', 1);
	}
	else if ($params['inplace']=='textarea')
	{

	    $tpl->set('textarea', 1);
	}
	return $tpl->parse( $params['inplace'] ? 'oce.html:inplace' : 'oce.html:default' );
}
return '';
?>