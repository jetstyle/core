<?
/*
	DBDataEdit -- класс для работы с таблицами в БД: запись данных в БД
	---------
	
	* DBDataEdit( &$rh, $table_name, $SELECT_FIELDS=array(), $where="", $order_by="", $limit="", $UPDATE_FIELDS=array() )
			конструктор, инициилизирует $UPDATE_FIELDS через $SELECT_FIELDS без 1го элемента
			расставляет дефолтные значения суффиксов и префиксов
			вызывает также DBDataView::DBDataView
	
  * Update($id,$where="") - обновляет запись, соответствующую id=$id (AND $where)
  		значения полей берутся из $rh->GLOBALS, при этом к именам добавляются суффикс и префикс
	
  * UpdateArr($IDS,$where="") - выполняет Update($IDS[$i],$where) для каждого элемента массива $IDS
  		для $i-й записи к суффиксу добавляется "_$i"
	
  * Delete($id,$where="") - удаляет запись, соответствующую id=$id (AND $where)
	
  * DeleteArr($id,$where="") - выполняет Delete($IDS[$i],$where) для каждого элемента массива $IDS
	
  * AddNew($_VALUES=array()) - добавляет запись в БД
  		значения полей берутся из $rh->GLOBALS, при этом к именам добавляются суффикс и префикс
  			а так же "суффикс нового"
  		$_VALUES - хэш "имя поля"=>"значение", он перекрывает данные из $rh->GLOBALS при вставке
	  		возвращает id новой записи
	
  * AddNewArr($N,$_VALUES=array()) - добавляет N записей в БД, вызывая N раз AddNew
  		для каждого $i=1,N к "суффиксу нового" добавляется "_$i"
	
  var $UPDATE_FIELDS - список полей для обновления
	
  var $prefix	- префикс для имён полей при извлечении значений из $rh->GLOBALS
	
  var $suffix	- суффиксс для имён полей при извлечении значений из $rh->GLOBALS
	
  var $suffix_new - суффиксс для имён полей при извлечении значений из $rh->GLOBALS
  									применяется при вставке, добавляется к $suffix
	
=============================================================== v.1 (Zharik)
*/
	
	$this->UseClass('DBDataView');
	
class DBDataEdit extends DBDataView {
	
	var $UPDATE_FIELDS = array();
	
	var $prefix = "";
	var $suffix = "";
	var $new_suffix = "";
	
	function DBDataEdit( &$rh, $table_name, $SELECT_FIELDS=array(), $where="", $order_by="", $limit="", $UPDATE_FIELDS=array() ){
		DBDataView::DBDataView( $rh, $table_name, $SELECT_FIELDS, $where, $order_by, $limit );
		$this->UPDATE_FIELDS = $UPDATE_FIELDS;
		$this->_CheckUpdateFields();
		//defaults
		$this->prefix = $this->suffix = "";
		$this->new_suffix = "_new";
	}
	
	function _CheckUpdateFields(){
		if($this->UPDATE_FIELDS=='none') return;
		if( !is_array($this->UPDATE_FIELDS) || count($this->UPDATE_FIELDS)==0 ){
			$this->UPDATE_FIELDS = $this->SELECT_FIELDS;
			array_shift($this->UPDATE_FIELDS);//the first element is supposed to be ID
		}
	}
	
  function Update($id,$where=""){
		if($this->UPDATE_FIELDS=='none') 
            return false;
		if( !count($this->UPDATE_FIELDS) ){
			$this->rh->debug->Error('DBDataEdit::Update - $UPDATE_FIELDS пусто, $table_name='.$this->table_name);
			return false;
		}
  	//aliaces
  	$db =& $this->rh->db;
  	$rh =& $this->rh;
  	//function
  	$sql = "UPDATE ".$this->table_name;
  	$sql .= " SET ";
  	//get fields values
  	for($i=0;$i<count($this->UPDATE_FIELDS);$i++){
  		$_field = $this->UPDATE_FIELDS[$i];
  		$sql .= (($i)?", ":"").$_field."=".$db->Quote( $rh->GetVar( $this->prefix.$_field.$this->suffix ) )."";
  	}	
  	$sql .= " WHERE ".$this->SELECT_FIELDS[0]."='$id'".(($where)? " AND ".$where : "" );
	
  	$db->execute($sql);
  	
		return true;
  }
	
  function UpdateArr($IDS,$where=""){
  	$_suffix = $this->suffix;
  	for($i=0;$i<count($IDS);$i++){
  		$this->suffix = $_suffix."_".$IDS[$i];
  		$this->Update($IDS[$i],$where);
  	}	
  	$this->suffix = $_suffix;
  }
	
  function Delete($id,$where="")
  {
  	
  	//aliaces
  	$db =& $this->rh->db;
  	//function
  	$sql = "DELETE FROM ".$this->table_name." WHERE ".$this->SELECT_FIELDS[0]."='$id'";
    
  	if($where) $sql .= " AND ".$where;
  	$db->execute($sql);
  }
	
  function DeleteArr($IDS,$where=""){
  	for($i=0;$i<count($IDS);$i++)
  		$this->Delete($IDS[$i],$where);
  }
	
  function AddNew($_VALUES=array()){
  	//aliaces
  	$db =& $this->rh->db;
  	$rh =& $this->rh;
  	//function
  	$VALUES = array();
  	//base values
  	for($i=0;$i<count($this->UPDATE_FIELDS);$i++){
  		$_field = $this->UPDATE_FIELDS[$i];
  		$VALUES[$_field] = $rh->GetVar( $this->prefix.$_field.$this->suffix.$this->new_suffix );
  	}
  	//manual values may be
    if(!is_array($_VALUES)) $_VALUES = array();
  	$VALUES = array_merge($VALUES,$_VALUES);
  	//execute
  	reset($VALUES);
  	$sql1 = $sql2 = "";
  	foreach($VALUES as $field=>$value){
  		$sql1 .= ((strlen($sql1) > 0)?",":"").$field;
  		$sql2 .= ((strlen($sql2) > 0)?",":"").$db->Quote( $VALUES[$field] );
  	}
  	$sql = "INSERT INTO ".$this->table_name."($sql1) VALUES($sql2)";
           
  	$isert_id = $db->insert($sql);
  	return $isert_id;
  }
	
  function AddNewArr($N,$VALUES=array()){
  	$_new_suffix = $this->new_suffix;
  	for($i=0;$i<$N;$i++){
  		$this->new_suffix = $_new_suffix."_".$i;
  		$this->AddNew($VALUES);
  	}	
  	$this->new_suffix = $_new_suffix;
  }
	
	//перенесено из DBDataEditTree
	//поскольку эта же операция потребуется при работе со списками
	//предаврительно, DBDataView::Load нужно делать при DBDataView->result_mode==1
	function Exchange( $id1, $id2 ){
		//check data
		if( $id1==$id2 || !$id1 || !$id2 )
			$this->rh->debug->Error('DBDataEdit::Exchange -  $id1'.$id1.', $id2'.$id2);
		//shortcuts
		$item1 = (object) $this->FindById($id1);
		$item2 = (object) $this->FindById($id2);
		//with the same _parent only
		if( $item1->_parent == $item2->_parent ){
			$db =& $this->rh->db;
			$db->execute("UPDATE ".$this->table_name." SET _order='".$item2->_order."' WHERE ".$this->SELECT_FIELDS[0]."='".$item1->id."'");
			$db->execute("UPDATE ".$this->table_name." SET _order='".$item1->_order."' WHERE ".$this->SELECT_FIELDS[0]."='".$item2->id."'");
			return true;
		}else return false;
	}
	
}

?>