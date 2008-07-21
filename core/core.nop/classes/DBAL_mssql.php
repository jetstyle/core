<?php
/*
  ������ ������� ���������� �� ���������� ����

  DBAL_mssql( &$dbal )

  ---------

  ���� ����� -- low-level mssql dbal. 
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

=============================================================== v.0 (Kukutz)
*/

class DBAL_mssql
{
  var $rh;     // $this->rh->debug->Trace (..->Error)
  var $dbal;   // higher level
  var $dblink; // �������� � mssql db

  function DBAL_mssql( &$dbal )
  {
    $this->rh = &$dbal->rh;
    $this->dbal = &$dbal;
  }

  function Connect()
  {
    if(!extension_loaded("mssql")) dl("php_mssql.dll");
	
	if (!$this->dblink = @mssql_connect($this->rh->db_host, $this->rh->db_user, $this->rh->db_password))
	{
		throw new DbException("Mssql connect error. Host=<b>" . $this->rh->db_host . "</b>, User=<b>" . $this->rh->db_user . "</b>", 1);
	}

	if (!mysql_select_db($this->rh->db_name, $this->dblink))
	{
		throw new DbException("Mssql database \"" . $this->rh->db_name . "\" select error", 2);
	}

  }

  function Close() { /* � ����� ������ ������ */ }

  // ������ ���������� �������� ���������
  // zharik@jetstyle: works pretty good, afaik
  function Quote( $value ) 
  { 
    return "'".str_replace("'","''",$value)."'";
  }

  // ������ ����� SQL
  function Query( $sql, $limit=0, $offset=0 ) 
  {
    // 0. patch query for limit
    if ($limit > 0)
    {
      $query = preg_replace( "/^select/i", "select top ".$limit, $query );
    }

    // ????? offset is still not implemented due no need yet.
    if ($offset > 0) 
      return $this->dbal->_Error( "MSSQL [offset] not implemented =(" );

    // 1. execute
	if (!$result = mssql_query($sql, $this->dblink))
		throw new DbException("Mssql query \"" . $sql . "\" error");
	
    return $result;
  }
  
  function InsertId()
  { 
     return $this->dbal->_Error( "MSSQL InsertId not implemented =(" );
  }
  

  function FetchAssoc( $handle ) 
  { return mssql_fetch_assoc($handle); }

  function FetchObject( $handle ) 
  { return mssql_fetch_object($handle); }

  function FreeResult( $handle ) 
  { return mssql_free_result($handle); }


// EOC{ DBAL_mssql } 
}


?>