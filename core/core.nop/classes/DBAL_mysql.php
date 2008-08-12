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
	protected $dblink; // �������� � mysql db

	public function __construct(){}

	public function connect()
	{
		if (!$this->dblink = @mysql_connect(Config::get('db_host'), Config::get('db_user'), Config::get('db_password')))
		{
			throw new DbException("Connect failed: Host=<b>" . Config::get('db_host') . "</b>, User=<b>" . Config::get('db_user') . "</b>");
		}
		
		Config::set('db_password', '');
		
		if (!mysql_select_db(Config::get('db_name'), $this->dblink))
		{
			throw new DbException("Database \"" . Config::get('db_name') . "\" select error");
		}
	}

	public function close() { /* � ����� ������ ������ */ }

	// ������ ���������� �������� ���������
	public function quote( $value )
	{
		if (is_array($value))
		{
			$ret = array_map(array(&$this, "quote"), $value);
			$ret = implode (",", $ret);
			return $ret;
		}
		elseif (is_numeric($value))
			return $value;
		else
			return "'".mysql_real_escape_string($value)."'";
	}

	// ������ ����� SQL
	public function query( $sql, $limit=0, $offset=0 )
	{
		// 0. patch query for limit, offset
		if ($limit > 0)
		{
			if ($offset > 0)
			{ 
				$sql.= " LIMIT $offset, $limit";
			}
			else
			{             
				$sql.= " LIMIT $limit";
			}
		}
		elseif ($offset > 0) 
		{
			$sql.= " LIMIT $offset, -1";
		}

		// 1. execute
		if (!$result = mysql_query($sql, $this->dblink))
			throw new DbException("Query failed: <div class=\"query\">" . $this->formatSql($sql) . "</div>");
		
		if (!is_resource($result))
		{
			$result = false;
		}
			
		return $result;
	}

	public function insertId()
	{ return mysql_insert_id( $this->dblink ); }

	public function fetchAssoc( $handle )
	{ return mysql_fetch_assoc($handle);}

	public function fetchObject( $handle )
	{ return mysql_fetch_object($handle); }

	public function freeResult( $handle )
	{ return mysql_free_result($handle); }

	public function affectedRows()
	{ return mysql_affected_rows($this->dblink); }

	public function getNumRows($handle)
	{ return mysql_num_rows($handle); }
	
	protected function formatSql($sql)
	{
		return preg_replace('/((select|from|((left|right|inner)\s*?join)|where|order|group|having|limit))\s/i', '<br /><b>$1</b> ', $sql);
	}

	// EOC{ DBAL_mysql }
}
?>