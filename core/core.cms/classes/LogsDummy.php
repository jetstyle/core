<?
/*
	������� ������ ����������� ������ �����������.
	��� �� �����, ����� �� ����� �����������, �� ������ ������� if($rh->trace_logs)...
*/
	
class LogsDummy {
	
	function Logs(){}	
	function PutClass(){}	
	function Put(){}	
	function ParseLink(){}
	
	//��� ����� ����� �������� ����? ����������� � ��������.
	function CheckAccess(){
		return $this->rh->prp->user['role_id'] == ROLE_GOD;
	}
}

?>