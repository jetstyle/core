<?php
//!oce
if (Config::get('db_disable'))
{
	return '';
}

$controller = Locator::get('controller');

if ( !isset($params['module']) )
{

    if ( in_array($controller['controller'], array('home', 'content', 'catalogue_simple', 'feedback' ) ) )
    {
	 $params['module']='content';
	 $params['id']= $controller['id'];
    }
    else
    {
         $params['module']=$controller['controller'];
	 
	 if ( ! $params['id'] && $controller->model['id'] )
	    $params['id']=  $controller->model['id'] ;
	 
	 //var_dump($controller->model->);
	 //die();
	//$params['module']="texts";
    }
}


if ( ! $params['id'] && !$params['model'] )
{
    foreach ( $params as  $i=>$p)
	if ( is_object($p) && get_class($p)=="DBModel" )
	    {
		$params['id'] = $p['id'];
		
		unset($params[$i]);
		break;
	    }
}
elseif (  ! $params['id'] )
{
    $params['id'] = $params['model']['id'];
}

//var_dump($params);

if (!$params['field']) 
    $params['field'] = 'text';


//Любой контент. Но мы его не выводим, только ссылку на редактирование
if ($params['module'] != 'texts' && $params['module'] != 'textsref')
{
	$id = $params['id'];
	if(!$id)
	{
		echo "<font color='red'><strong>[id пуст]</strong></font>";
	}
	/*
	//пририсовываем OCE
	$params = array(
          'module'=>$type, 
          'id'=>$id,
          'width'=>'800',
          'height'=>'600',
	);
	*/

}
//Таблица texts
else
{
	$supertag = $params['tag'] ? $params['tag'] : $params[0];
//	echo 'tags';
	if(!$supertag)
		$supertag = $tpl->get( $params["var"] );
	if(!$supertag)
		echo "<font color='red'><strong>[\$supertag пуст]</strong></font>";

	$custom = array('table'=>'??texts', 'module'=>'texts', 'field'=>'text_pre', 'add_fields'=>',type'.( isset($params['field']) ? ",".$params['field'] : "" ));

	//грузим текст по супертагу
	if (Config::exists('__texts_'.$supertag))
	{
		$r = Config::get('__texts_'.$supertag);
	}
	else
	{
		$db = &Locator::get('db');
		if ( $params['referer'] )
		{
		    $ref = $_SERVER['HTTP_REFERER'];
		    
		    $sql = "SELECT t.id, t.".$custom['field']. " ".$custom['add_fields']." FROM ".$custom['table']." as t 
			    INNER JOIN ??texts_referers as r WHERE t._supertag='$supertag' AND t._state=0 
			    AND (".$db->quote($ref)." LIKE r.title OR r.title='')  AND t.channel_id=r.channel_id ORDER by r.title DESC";
		    
		    $custom['module'] = 'textsref';
		}
		else
		{
		    $sql = "SELECT id,".$custom['field'].$custom['add_fields']." FROM ".$custom['table']." WHERE _supertag='$supertag' AND _state=0 ";
		}
		$r = $db->queryOne($sql);

		//если записи с реферером нет - ищем без него
		/*
		if ( !$r["id"] && $params['referrer'] )
		{
			$sql = "SELECT id,".$custom['field'].$custom['add_fields']." FROM ".$custom['table']." WHERE _supertag='$supertag' AND _state=0 ";
			$r = $db->queryOne($sql);
		}
		*/

		//если такой записи нет И не используется реферер - создаём её
		if( !$r["id"] &&  !$params['referer']  )
		{
			$r["id"] = $db->insert("INSERT INTO ".$custom['table']."(title,_supertag,_created,_modified) VALUES('$supertag','$supertag',NULL,NULL)");
		}
		
		
		Config::set('__texts_'.$supertag, $r);
	}

	//пририсовываем OCE
	/*
	$params = array(
          'module'=>$custom['module'], 
          'id'=>$r['id'],
          'width'=>'800',
          'height'=> $r['type']==1 ? 500 : '600'
	) ;
	*/
	$params['module']= $custom['module']; 
	$params['id'] = $r['id'];
	$content = ( $params['field'] && isset( $r[$params['field']] ) ) ? $r[$params['field']] : $r[$custom['field']];
	if ($params['nl2br'])
	    $content = nl2br( $content );
	echo $content;
}
//$para['show'] = $params['show'] ? true : false;
//$para['inplace'] = $params['inplace'];
//$para['field'] = $params['field'];
//$para['container'] = $params['container'];
//$para['thickbox'] = $params['thickbox'];
//$para['rows'] = $params['rows'];
//$para['cols'] = $params['cols'];
//----------------------------------------------------

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
/*
	$tpl->set('_module', $module);
	$tpl->set('_id', $id);
	$tpl->set('_href', (Config::exists('cms_url') ? Config::get('cms_url') : RequestInfo::$baseUrl."cms/").str_replace('::id::',$id,$oce[$module]).'hide_toolbar=1&popup=1' );
	$tpl->set('_width', $params['width'] ? $params['width'] : 500 );
	$tpl->set('_height', $params['height'] ? $params['height'] : 600 );
	$tpl->set('_title', $params['title'] ? $params['title'] : 'редактировать' );
	$tpl->set('_field', $params['field'] );
	
	
	if ( $params['rows']>0 )
	    $tpl->set('_rows', "rows='".$params['rows']."'" );
	if ( $params['cols']>0 )
	    $tpl->set('_cols', "cols='".$params['cols']."'" );
	*/
	$params['href'] = (Config::exists('cms_url') ? Config::get('cms_url') : RequestInfo::$baseUrl."cms/").str_replace('::id::',$id,$oce[$module]).'hide_toolbar=1&popup=1';
	//var_dump($params);
	$tpl->set("*", $params);	

	return  $params["_"] .  $tpl->parse( 'inplace.html' );
}
return '';
?>