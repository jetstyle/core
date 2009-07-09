<?php
/***
 * Just parses edit-link|editor out
 *
 * @oce.html
 *
 *
 */
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

	if( !$id && !$params['add'] )
	{
		Debug::trace("<span style='color: red; font-weight: bold;'>OCE: id not found, module=$module, id=$id, var=$var</span>");
	}

	$tpl->set('_module', $module);
	$tpl->set('_id', $id);
    if ($params['add'])
    {
        $oceLink = str_replace('id=::id::', '', $oce[$module]).'_new=1&';
    }
    else
    {
        $oceLink = str_replace('::id::', $id, $oce[$module]);
    }
	$tpl->set('_href', (Config::exists('cms_url') ? Config::get('cms_url') : RequestInfo::$baseUrl."cms/").$oceLink.'hide_toolbar=1&popup=1' );
	$tpl->set('_width', $params['width'] ? $params['width'] : 800 );
	$tpl->set('_height', $params['height'] ? $params['height'] : 600 );
	$tpl->set('_title', $params['title'] ? $params['title'] : 'редактировать' );
	$tpl->set('_field', $params['field'] );
	$tpl->set('_popup', isset($params['popup']) ? 1 : null );

	return $tpl->parse( 'oce.html' );
}
return '';
?>
