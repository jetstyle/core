<?
	/*
		������ � �������� �� HTCron (http://wackowiki.com/htCron).

  */
	
class Module_HTCron extends Module {
	
  var $rh;
  var $table_htcron;

  function InitInstance(){
  	$this->table_htcron = $this->rh->project_name.'_htcron';
  }

  //���������, ���� �� ������ � ��������� ��������
  //���� ���, �� ������ � � ������� �������� ���������� ������� (��� ���������)
  function CheckRecord( $command, $spec, $time=false ){
  	$db =& $this->rh->db;
  	$rs = $db->execute("SELECT * FROM ".$this->table_htcron." WHERE command='".$command."'");
  	if(!$rs->EOF)
  		return $rs->fields;
  	else{	
  		if(!$time) $time = time();
  		$db->execute("INSERT INTO ".$this->table_htcron."(command,spec,last) VALUES('".$command."','".$spec."','".$time."')");
  		return array(
  			'command'=>$command,
  			'spec'=>$spec,
  			'last'=>$time,
  		);
  	}
  }

  //������� ������ �� �������
  //$r - id ������ ��� ��� � ����� 'id'
  function DeleteRecord( $r ){
  	$this->rh->db->execute("DELETE FROM ".$this->table_htcron." WHERE id='".( is_array($r) ? $r['id'] : $r )."'");
  }	
}

?>