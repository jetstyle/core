<?

/*

Таблица для кэширования результатов:
-----------------------------------

CREATE TABLE [$rh->project_name]_search (
  href varchar(255) NOT NULL default '',
  title varchar(255) NOT NULL default '',
  text text NOT NULL,
  session_id int(11) NOT NULL default '0',
  inserted int(11) NOT NULL default '0',
  _order int(11) NOT NULL default '0',
  KEY session_id(session_id),
  KEY _order(_order),
  KEY inserted(inserted)
) TYPE=MyISAM;

*/

class SearchCache {
	
	var $rh; //ссылка на $rh
	
	var $ITEMS = array();
	var $cache_table_name;
	var $arrows; //постраничный рубрикатор
	
	var $sid_varname = 'ssid';  //имя переменной запроса, в которой храница ID для кэша
	var $do_varname = 'search'; //имя переменной запроса, определяющей новый поиск
	var $string_varname = 'qstr'; //имя переменной запроса, в которой храница строка для поиска
	var $redirect_to = '/?';  //адрес для перенаправления при новом поиске
	
	var $string = ''; //оригинальная строка для поиска
  
  var $SELECT_FIELDS = array('href','title','text');//какие поля грузить из кэша?
	
	function SearchCache(&$rh){
		$this->rh =& $rh;
		$this->cache_table_name = $rh->project_name.'_search';
	}
	
	function Handle(){
		$rh =& $this->rh;
		
		//строка для поиска
		$this->string = $rh->GetVar($this->string_varname);
		$rh->tpl->Assign('_qstr',$this->string);
		
		if( $rh->GetVar($this->do_varname) ){
			
			//новый поиск
			$this->Search($this->string);
			$session_id = $this->SessionID();
			$this->Save($session_id);
			$rh->Redirect( $this->redirect_to.$this->sid_varname.'='.$session_id.'&'.$this->string_varname.'='.urlencode($this->string) );
			
		}else{
			
			//восстанавливаем результаты из кэша
			$session_id = urldecode($rh->GetVar($this->sid_varname));
			$this->Restore($session_id);
			
			//настраиваем постраничный рубриктор
			if( $this->arrows ){
				$this->arrows->Set( $this->sid_varname, $session_id );
				$this->arrows->Set( $this->string_varname, $this->string );
				$rh->tpl->Assign( '_start', $this->arrows->start*$this->arrows->outpice + 1 );
			}
			
			return $session_id;
		}
	}
	
	//собственно поиск	
	function Search($string){
		/*
		 перекрывать в будущем,
     нужно заполнить $this->string и $this->ITEMS,
     формат записи в $this->ITEMS:
       array(
        'title'=>,
        'text'=>,
        'href'=>,
        '_order'=>,
       );
    */
	}
	
	function SessionID(){
		$db =& $this->rh->db;
		//generate session_id
		do{
			$session_id = rand(1,100000);
			$rs = $db->SelectLimit("SELECT session_id FROM ".$this->cache_table_name." WHERE session_id='".$session_id."'",1);
		}while(!$rs->EOF);
		return $session_id;
	}
	
	function Save($session_id){
		$db =& $this->rh->db;
		//save data
		$time = time();
		foreach($this->ITEMS as $r){
			$text = addslashes(strip_tags(preg_replace("/<br.*?>/i"," ",trim($r['text']))));
			$title = addslashes($r['title']);
			if($this->string!=''){
				$text = preg_replace( "/(\W)(".$this->string.")/i","\\1<font color=\"red\">\\2</font>", $text );
				$title = preg_replace( "/(\W)(".$this->string.")/i","\\1<font color=\"red\">\\2</font>", $title );
			}
			$db->execute("INSERT INTO ".$this->cache_table_name."(href,title,text,session_id,inserted,_order) VALUES('".$r['href']."','".$title."','".$text."','".$session_id."','".$time."','".$r['_order']."')");
		}
    //прибить старые
		$this->KillOld();
	}
	
	function Restore($session_id){
		//select
		$this->rh->UseClass('DBDataView',0);
		$this->list =& new DBDataView( $this->rh, $this->cache_table_name, $this->SELECT_FIELDS, "session_id='".$session_id."'", '_order ASC, inserted ASC');
		$this->list->arrows =& $this->arrows;
		$this->list->Load();
		$this->ITEMS =& $this->list->ITEMS;
    //прибить старые
		$this->KillOld();
	}
	
	function KillOld(){
		$this->rh->db->execute("DELETE FROM ".$this->cache_table_name." WHERE inserted<'".(time()-3600)."'");
	}
	
}

?>