<?php
/*
  ������ ������� ���������� �� ���������� ����

  DBAL_mysql( &$dbal )

  ---------

  ���� ����� -- low-level mysql dbal. 
  �� �������������� ������������� �� higher level

  ---------

  // ������ ���������� �������� ���������

  * Quote( $value ) -- ����������� ��������, ����� ��� ���������� ��� SQL
		- ���������� ���-�� ����� '13' ��� '������' 
		  (������ � ����������� ���������, ������� ������ ��������� ��� �� ����)

  // ��������� ������� SQL � ��������� � ���� �����

  * Query( $sql, $limit=0, $offset=0 ) -- ������ ������, ��������� "��������"
  * FetchAssoc ( $db_handle ) -- ���������� ��� ��������� ������, ������� "��������" "����"
  * FetchObject( $db_handle ) -- �� ��, ������ ���������� ������
  * FreeResult( $db_handle ) -- ����������� ������ ��������, ��������� "��������"

  // ������

  * InsertId() -- ���������� ������������� ������ ��� ����������� ������

  * Close()   -- ��������� ��, ��� ����� (�� �� ��� ���������� "��������")
  * Connect() -- ������������� ���������� � ��

  ---------

  ����� DBAL ����� ����� ��������� � $rh:

  $rh->db_host     = "localhost"
  $rh->db_user     = "username"
  $rh->db_password = "pwd"
  $rh->db_name     = "project_db"

=============================================================== v.1 (kuso@npj)
 */

class DBAL_mysql
{
	var $rh;     // $this->rh->debug->Trace (..->Error)
	var $dbal;   // higher level
	var $dblink; // �������� � mysql db

	function DBAL_mysql( &$dbal )
	{
		$this->rh = &$dbal->rh;
		$this->dbal = &$dbal;
	}

	function Connect()
	{
		if(!extension_loaded("mysql")) dl("mysql.so");
		$this->dblink = mysql_connect($this->rh->db_host, 
			$this->rh->db_user,
			$this->rh->db_password
		);
		mysql_select_db($this->rh->db_name, $this->dblink);
	}

	function Close() { /* � ����� ������ ������ */ }

	// ������ ���������� �������� ���������
    function Quote( $value ) 
    {
     if (is_array($value))
     {
        $ret = array_map(array(&$this, "quote"), $value);
        $ret = implode (",", $ret);
        return $ret;
     }
     //else if (is_numeric($value))
       // return $value;
     else
        return "'".mysql_escape_string($value)."'"; 
    }

	// ������ ����� SQL
	function Query( $sql, $limit=0, $offset=0 ) 
	{
		// 0. patch query for limit, offset
		if ($limit > 0)
			if ($offset > 0) $sql.= " LIMIT $offset, $limit";
			else             $sql.= " LIMIT $limit";
		else
			if ($offset > 0) $sql.= " LIMIT $offset, -1";

		// 1. execute
		if (!$result = mysql_query($sql, $this->dblink))
			return $this->dbal->_Error( "Query failed: ".$sql." (".mysql_error().")" );

		if (!is_resource($result)) $result = FALSE;

		return $result;
	}

	function InsertId()
	{ return mysql_insert_id( $this->dblink ); }

	function FetchAssoc( $handle ) 
	{ return mysql_fetch_assoc($handle); }

	function FetchObject( $handle ) 
	{ return mysql_fetch_object($handle); }

	function FreeResult( $handle ) 
	{ return mysql_free_result($handle); }

	function AffectedRows( ) 
	{ return mysql_affected_rows($this->dblink); }

    function GetNumRows($handle)
    {
      return mysql_num_rows($handle);
    }

	// EOC{ DBAL_mysql } 
}


?>
