<?php

  $type = $params['type'] ? $params['type'] : 'texts';
  $good = true;

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
  else
  {
    $supertag = $params['tag'] ? $params['tag'] : $params[0];
    if(!$supertag)
      $supertag = $rh->tpl->Get( $params["var"] );
    if(!$supertag)
      echo "<font color='red'><strong>[\$supertag пуст]</strong></font>";

	if ($type == 'banners')
		$custom = array('table'=>$db->prefix.'banners', 'module'=>'banners', 'field'=>'text', 'add_fields'=>'');
	else
		$custom = array('table'=>$db->prefix.'texts', 'module'=>'texts', 'field'=>'text_pre', 'add_fields'=>',type'.( isset($params['field']) ? ",".$params['field'] : "" ));

    //грузим текст по супертагу 
	if ($rh->TEXTS[$supertag])
	{
		$r = $rh->TEXTS[$supertag];
	}
	else
	{
	    $sql = "SELECT id,".$custom['field'].$custom['add_fields']." FROM ".$custom['table']." WHERE _supertag='$supertag' AND _state=0";
	    $r = $db->queryOne($sql);

	    //если такой записи нет - создаём её
	    if(!$r["id"])
	    	$r["id"] = $db->insert("INSERT INTO ".$custom['table']."(title,_supertag,_created,_modified) VALUES('$supertag','$supertag',NULL,NULL)");
	}
    $rh->TEXTS[$supertag] = $r;

    //пририсовываем OCE
    $para = array( 
          'module'=>$custom['module'], 
          'id'=>$r['id'],
          'width'=>'800',
          'height'=> $r['type']==1 ? 500 : '600',
         ) ;

//var_dump($params);
	  echo ( $params['field'] && isset( $r[$params['field']] ) ) ? $r[$params['field']] : $r[$custom['field']];  
  }

  
	if ($good && !isset( $params["noedit"] ) )
	    echo $rh->tpl->Action( 'oce', $para );
	
?>
