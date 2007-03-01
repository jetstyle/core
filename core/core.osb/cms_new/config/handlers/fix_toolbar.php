<?
	include( $rh->FindScript('handlers','_start') );
  
  //не авторизован?
  if( !$prp->IsAuth() )
    $rh->redirect( $rh->url.'login' );
  
  //таблица должна содержать древесную структуру
  //проверяем
  $rs = $db->execute("SHOW FIELDS FROM ".$rh->project_name."_toolbar");
  $FIELDS = $rs->GetArray();
  $OK = false;
  foreach($FIELDS as $r)
    if( $r["Field"]=="_parent" ){
      $OK = true;
      break;
    }
  //исправляем
  if(!$OK){
    $db->execute(
                  "ALTER TABLE `".$rh->project_name."_toolbar` ADD `_parent` INT NOT NULL ,
                  ADD `_level` INT NOT NULL ,
                  ADD `_left` INT NOT NULL ,
                  ADD `_right` INT NOT NULL ;"
                  );
    $db->execute("ALTER TABLE `".$rh->project_name."_toolbar` ADD INDEX ( `_parent` , `_level` , `_left` , `_right` ) ;");
  }
  
  //восстанавливаем древесную статистику
  //грузим дерево
  $rh->UseClass("DBDataEditTree");
  $tree =& new DBDataEditTree( $rh, $rh->project_name."_toolbar", array("id") );
  $tree->Load();
  //восстанавливаем структуру
  $tree->Restore();
  
  echo "done";
?>