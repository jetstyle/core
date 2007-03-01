<?
	//продполагается, что какой-то принципал уже загружен
	
/*
 CREATE TABLE `_cms_logs` (
	`user_id` INT NOT NULL,
	`item_id` INT NOT NULL,
	`class_id` INT NOT NULL,
	`inserted` DATETIME NOT NULL,
	`action` VARCHAR(255) NOT NULL,
	`title` VARCHAR(255) NOT NULL,
	`link` VARCHAR(255) NOT NULL,
	INDEX (`user_id`, `item_id`, `class_id`, `inserted`)
);
 CREATE TABLE `_cms_logs_classes` (
	`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
	`title` VARCHAR(255) NOT NULL,
	INDEX (`title`)
);
*/
	
class Logs {
	
  var $rh;
	var $table_logs = ''; //таблица, куда класть логи
	var $table_classes = ''; //таблица, куда класть псевдокласссы записей
	var $template = 'logs_link.html';  //шыблон вывода ссылки на просмотр логов
	
	function Logs(&$rh){
		$this->rh =& $rh;
		$this->table_logs = $this->rh->project_name."_cms_logs";
		$this->table_classes = $this->rh->project_name."_cms_logs_classes";
	}
	
	function PutClass( $class_title, $add=true ){
		$db =& $this->rh->db;
		$rs = $db->execute("SELECT id FROM ".$this->table_classes." WHERE title='$class_title'");
		if(!$rs->EOF) return $rs->fields['id'];
		else if($add){
			$db->execute("INSERT INTO ".$this->table_classes."(title) VALUES('$class_title')");
			return $db->Insert_ID();
		}else return 0;
	}
	
	function Put( $action, $item_id, $class, $title, $link, $user_id=0 ){
		if(!$user_id) $user_id = $this->rh->prp->user['id'];
		//отрезаем домен и относительный путь. Криво, я знаю.
		$link = str_replace( $this->rh->url, '', $link );
		$this->rh->db->execute("INSERT DELAYED INTO ".$this->table_logs."(user_id,item_id,class_id,inserted,action,title,link) VALUES('$user_id','$item_id','".$this->PutClass($class)."',NOW(),'$action','$title','$link')");
	}
	
	function ParseLink( $class_title, $item_id, $store_to='', $append=false ){
		if(!$this->CheckAccess()) return '';
		$tpl =& $this->rh->tpl;
		$class_id = $this->PutClass( $class_title, false );
		if(!$class_id) return '';
		$tpl->Assign( '_logs_href', $this->rh->url.'do/Logs?class_id='.$class_id.'&item_id='.$item_id.'&hide_toolbar=1' );
		return $tpl->Parse( $this->template, $store_to, $append );
	}
	
	//кто имеет право смотреть логи? Перегружать в потомках.
	function CheckAccess(){
		return $this->rh->prp->user['role_id'] == ROLE_GOD;
	}
}

?>