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
		$custom = array('table'=>$db->prefix.'texts', 'module'=>'texts', 'field'=>'text_pre', 'add_fields'=>',type');

    //грузим текст по супертагу 
    $sql = "SELECT id,".$custom['field'].$custom['add_fields']." FROM ".$custom['table']." WHERE _supertag='$supertag' AND _state=0";
    $r = $db->queryOne($sql);
    //$r = $rs->fields;

    //если такой записи нет - создаём её
    if(!$r["id"])
    	$r["id"] = $db->insert("INSERT INTO ".$custom['table']."(title,_supertag,_created,_modified) VALUES('$supertag','$supertag',NULL,NULL)");

    //пририсовываем OCE
    $para = array( 
          'module'=>$custom['module'], 
          'id'=>$r['id'],
          'width'=>'800',
          'height'=> $r['type']==1 ? 500 : '600',
         ) ;

	  echo $r[$custom['field']];  
  }

  
	if ($good)
	    echo $rh->tpl->Action( 'oce', $para );
	
?>
