<?php
//!oce

if (Config::get('db_disable'))
{
	return '';
}

$type = $params['type'] ? $params['type'] : 'texts';
$good = true;

//Любой контент. Но мы его не выводим, только ссылку на редактирование
if ($type != 'texts')
{
	$id = $params['id'];
	if(!$id)
	{
		echo "<font color='red'><strong>[id пуст]</strong></font>";
		$good = false;
	}

	//пририсовываем OCE
	$para = array(
          'module'=>$type, 
          'id'=>$id,
          'width'=>'800',
          'height'=>'600',
	);

}
//Таблица texts
else
{
	$supertag = $params['tag'] ? $params['tag'] : $params[0];
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
	$para = array(
          'module'=>$custom['module'], 
          'id'=>$r['id'],
          'width'=>'800',
          'height'=> $r['type']==1 ? 500 : '600'
	) ;

	$content = ( $params['field'] && isset( $r[$params['field']] ) ) ? $r[$params['field']] : $r[$custom['field']];
	echo $content;
}
$para['show'] = $params['show'] ? true : false;
$para['inplace'] = $params['inplace'];
$para['field'] = $params['field'];

$para['thickbox'] = $params['thickbox'];

if ($good && !isset( $params["noedit"] ) )
    echo $tpl->action( 'oce', $para );
?>
