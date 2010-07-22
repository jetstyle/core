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

 db_host     = "localhost"
 db_user     = "username"
 db_password = "pwd"
 db_name     = "project_db"

 =============================================================== v.1 (kuso@npj)
 */

class DBAL_mysql
{
	protected $dblink; // рукоятка к mysql db

	public function __construct(){}

	public function connect()
	{
		if (!$this->dblink = @mysql_connect(Config::get('db_host'), Config::get('db_user'), Config::get('db_password')))
		{
			$humanMessage = 'Ошибка при соединении с сервером базы данных.';
            $humanMessage .= '<br />';
            $humanMessage .= 'Проверьте правильность указания имени пользователя и пароля для сервера базы данных в файле настроек <span class="example">config/config.yml</span>';
            
            throw new DbException("Connect failed: Host=<b>" . Config::get('db_host') . "</b>, User=<b>" . Config::get('db_user') . "</b>", '', $humanMessage);
		}
		
		Config::free('db_password');
		
		if (!mysql_select_db(Config::get('db_name'), $this->dblink))
		{
			$humanMessage = 'Ошибка при выборе базы данных.';
            $humanMessage .= '<br />';
            $humanMessage .= 'Проверьте, существует ли база данных <span class="example">'.Config::get('db_name').'</span> и достаточно ли прав для ее использования.';

            throw new DbException("Database \"" . Config::get('db_name') . "\" select error", '', $humanMessage);
		}
		
		if (Config::get('db_set_encoding')) 
		{
			$this->query("SET NAMES " . Config::get('db_set_encoding'));
		}
	}

	public function close() { /* в нашем случае ничего */ }

	// Защита строкового значения кавычками
	public function quote( $value )
	{
		if (is_array($value))
		{
			$ret = array_map(array(&$this, "quote"), $value);
			$ret = implode (",", $ret);
			return $ret;
		}
		else
			return "'".mysql_real_escape_string($value)."'";
	}

	// Прямой вызов SQL
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
