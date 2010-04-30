<?php
/*
  Нижний уровень абстракции от конкретной СУБД

  DBAL_mysql( &$dbal )

  ---------

  Этот класс -- low-level mysql dbal. 
  Он конструируется автоматически из higher level

  ---------

  // Защита строкового значения кавычками

  * Quote( $value ) -- обквочивает значение, делая его безопасным для SQL
		- возвращает что-то вроде '13' или 'строка' 
		  (вместе с правильными кавычками, которые вокруг добавлять уже не надо)

  // Упрощение вызовов SQL с возвратом в виде хэшей

  * Query( $sql, $limit=0, $offset=0 ) -- делает запрос, возвращая "рукоятку"
  * FetchAssoc ( $db_handle ) -- возвращает хэш фетченной строки, сдвигая "рукоятку" "вниз"
  * FetchObject( $db_handle ) -- то же, только возвращает объект
  * FreeResult( $db_handle ) -- освобождает память драйвера, сбрасывая "рукоятку"

  // Прочее

  * InsertId() -- возвращает идентификатор только что вставленной строки

  * Close()   -- закрывает всё, что может (но не все незакрытые "рукоятки")
  * Connect() -- устанавливает соединение с БД

  ---------

  Этому DBAL нужны такие параметры в $rh:

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
	var $dblink; // рукоятка к mysql db

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

	function Close() { /* в нашем случае ничего */ }

	// Защита строкового значения кавычками
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

	// Прямой вызов SQL
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
