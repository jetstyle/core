<?
/*
	Таблица сессий:
CREATE TABLE `???_sessions` (
	`id` INT NOT NULL,
	`ip` VARCHAR(20) NOT NULL,
	`inserted` INT NOT NULL,
	INDEX (`id`, `ip`, `inserted`)
);
*/


/*
  Нужно задавать при вызове:
	$session_table_name = $rh->project_name."_sessions";
	$session_cookie_name = "session_id";
*/
	
	$session_id = $_COOKIE[$session_cookie_name];
  $ip = ($_SERVER["HTTP_X_FORWARDED_FOR"]!="")?$_SERVER["HTTP_X_FORWARDED_FOR"]:$_SERVER["REMOTE_ADDR"];
	$time = time();
	
	//check session_id
	$rs = $db->execute("SELECT id FROM ".$session_table_name." WHERE id='".$session_id."' AND ip='".$ip."'");
	$session_id = $rs->fields['id'];
	
	if(!$session_id){
		//read existing sessids
		$rs = $db->execute("SELECT DISTINCT id FROM ".$session_table_name);
		$SESSIDS = $rs->GetArray();
		//generate new sessid
		do{
			$session_id = rand(1,1000000);
		}while(in_array($session_id,$SESSIDS));
		$db->execute("INSERT INTO ".$session_table_name."(id,ip,inserted) VALUES('".$session_id."','".$ip."','".$time."')");
		setcookie( $session_cookie_name, $session_id );
		$SESSIDS[] = $session_id;
	}else
		//update existing
		$db->execute("UPDATE ".$session_table_name." SET inserted='".$time."' WHERE id='".$session_id."'");
	
	//kill old sessions
	$_time = $time - 86400;
	$rs = $db->execute("SELECT id FROM ".$session_table_name." WHERE inserted<'".$_time."'");
  $SIDS_DELETED = $rs->GetArray();
	$db->execute("DELETE FROM ".$session_table_name." WHERE inserted<'".$_time."'");
	
?>
