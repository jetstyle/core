<?
/*
	Затычка вместо нормального класса логирования.
	Что бы тогда, когда не нужно логирование, не писать повсюду if($rh->trace_logs)...
*/
	
class LogsDummy {
	
	function Logs(){}	
	function PutClass(){}	
	function Put(){}	
	function ParseLink(){}
	
	//кто имеет право смотреть логи? Перегружать в потомках.
	function CheckAccess(){
		return $this->rh->prp->user['role_id'] == ROLE_GOD;
	}
}

?>