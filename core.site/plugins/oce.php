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

	//if( !isset($oce[$module]) )
	//{
	//	Debug::trace("<span style='color: red; font-weight: bold;'>OCE: module not found, module=$module, id=$id, var=$var</span>");
	//	return '';
	//}

	if( !$id && !$params['add'] )
	{
		Debug::trace("<span style='color: red; font-weight: bold;'>OCE: id not found, module=$module, id=$id, var=$var</span>");
	}

    //set link
    $oceLink = $params['module'].'/form?';
    if ($id)
    {
        $oceLink .= 'id='.$id;
    }
    else if($params['add'])
    {
        $oceLink .= '_new=1';
    }

    //set title
    if (!$params['title'])
    {
        if ($params['add'])
        {
            $title = 'добавить';
        }
        else
        {
            $title = 'редактировать';
        }
    }
    else
    {
        $title = $params['title'];
    }

	$tpl->set('_module', $module);
	$tpl->set('_id', $id);
	$tpl->set('_href', (Config::exists('cms_url') ? Config::get('cms_url') : RequestInfo::$baseUrl."cms/do/").$oceLink.'&hide_toolbar=1&popup=1');
	$tpl->set('_width', $params['width'] ? $params['width'] : 800 );
	$tpl->set('_height', $params['height'] ? $params['height'] : 600 );
	$tpl->set('_title',  $title );
	$tpl->set('_field', $params['field'] );
	$tpl->set('_popup', isset($params['popup']) ? 1 : null );

	return $tpl->parse( 'oce.html' );
}
else
{
    return '';
}

?>
