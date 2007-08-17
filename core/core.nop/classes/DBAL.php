<?php

/*
  Абстракция от конкретной СУБД:
  * упрощение вызова sql-запросов
  * защита кавычками от sql-oriented violations

  DBAL( &$rh, $connect=true )

  ---------

  NB: вместо $rh можно передавать любой объект, содержащий свойства $this->db_* 
      и опционально $this->debug (объект класса Debug)

  Этот класс -- верхний уровень абстракции от СУБД. 
  Для конкретных СУБД реализован нижний уровень -- DBAL_****, через $rh->db_al="****"
  Файлы должны лежать в одном каталоге.

  ---------

  // Защита строкового значения кавычками

  * Quote( $value ) -- обквочивает значение, делая его безопасным для SQL
      - возвращает что-то вроде '13' или 'строка' 
        (вместе с правильными кавычками, которые вокруг добавлять уже не надо)

  // Упрощение вызовов SQL с возвратом в виде хэшей

  * Query( $sql, $limit=0, $offset=0 ) -- самый навороченный, используется просто ВСЕГДА
      - делает предобработку запроса (если пользователь -- админ, то "выключает" из запроса "active=1")
      - получает результат запроса
      - выкладывает его в массив из ассоциированных массивов (хэшей)
  * _Query( $sql, $limit=0, $offset=0 ) -- то же самое, только без предобратки
  * QueryOne( $sql ) -- то же, что Query, но берёт из БД и возвращает только первую запись хэшем
                        или false, если результат -- пустота.

  * Insert( $sql ) -- запрос типа INSERT, возвращаем идентификатор вставленной записи

  // Прочее

  * RecordCount( $sql ) -- чтобы не вызывать Query для этой задачи, возвращает число
  * Close() -- лучше бы вызывать в конце, а то мало ли что мы тут "напридумывали" в плане освобождения памяти
  * _Connect() -- присоединяется к БД, получая рукоятку. Вызывается из конструктора, если не указан спецпараметр

  ---------

  Типовые DBAL-параметры в $rh:

  $rh->db_host     = "localhost"
  $rh->db_user     = "username"
  $rh->db_password = "pwd"
  $rh->db_name     = "project_db"
  $rh->db_prefix   = "prefix_"

  $rh->db_al       = "mysql" <- по этой штуке мы ловим, какой дбал нам был нужен

  ---------

  Типичный приспособленный к этому дбалу запрос выглядит так ($db -- это дбаловский объект):

  $a = $db->Query( "select * from ".$db->prefix." where active=1 and section_id=". $db->Quote(35) );
  echo $a[0]["section_id"];

=============================================================== v.2 (kuso@npj)
*/

class DBAL
{
	var $rh; // use: $this->rh->debug->Trace (..->Error)
	var $lowlevel;

	/*
	// NB: не реализовано в первой версии (ForR2, ?????)
	var $active_from = "active=1";        // из чего и во что превращать статус "видимости"
	var $active_to   = "active=active";   // чего-то в БД для неавторизованного пользователя
	var $active_role = "editor";          // для какой "роли" это следует делать (пока задел на будущее)
	*/

	function DBAL(& $rh, $connect = true)
	{
		$this->rh = & $rh;
		$this->prefix = $rh->db_prefix;

		// создать низкоуровневый дбал
		require_once (dirname(__FILE__) . "/DBAL_" . $rh->db_al . ".php");
		$lowlevel_name = "DBAL_" . $rh->db_al;
		$lowlevel = & new $lowlevel_name ($this);
		$this->lowlevel = & $lowlevel;

		// connection, if any
		if ($connect)
			$this->_Connect();
	}

	function _Connect()
	{
		$this->lowlevel->Connect();
	}

	function Close()
	{
		$this->lowlevel->Close();
	}

	// Защита строкового значения кавычками
	function Quote($value)
	{
		return $this->lowlevel->Quote($value);
	}

	// Упрощение вызовов SQL с возвратом в виде хэшей
	function Query($sql, $limit = 0, $offset = 0)
	{
		// #1. patch sql for active
		// не реализовано в первой версии (ForR2, ?????)
		// #2. query
		return $this->_Query($sql, $limit, $offset);
	}

	function _Query($sql, $limit = 0, $offset = 0)
	{
		if(method_exists($this->rh->debug, 'mark'))
		{
			$this->rh->debug->mark('q');
		}
		
		$data = array ();
		//плейсхолдер для префикса
		$sql = str_replace("??", $this->prefix, $sql);

		if ($r = $this->lowlevel->Query($sql, $limit, $offset))
		{
			while ($row = $this->lowlevel->FetchAssoc($r))
			{
				$data[] = $row;
			}
			$this->lowlevel->FreeResult($r);
		}
		
		if($this->rh->enable_debug && $this->rh->explain_queries)	
		{
			if(!(strpos(strtolower($sql), 'select') === false))	
			{
				$_data = array();
				if ($r = $this->lowlevel->Query("EXPLAIN ".$sql, $limit, $offset))
				{
					while ($row = $this->lowlevel->FetchAssoc($r)) 
					{
						$_data[] = $row;
					}
					$this->lowlevel->FreeResult($r);
				}
				if(is_array($_data) && !empty($_data))	
				{
					foreach($_data AS $i => $r)	
					{
						if($i == 0)	
						{
							$head = array_keys($r);
							foreach($head AS $rr)	
							{
								$out .= "<td>".$rr."</td>";
							}
							$out = "<tr>".$out."</tr>";
						}

						foreach($r AS $rr)	
						{
							$row .= "<td>".$rr."</td>";
						}
						$out .= "<tr>".$row."</tr>";
						$row = '';
					}
					
					$out = "<table class=\'debug_table\'>".$out."</table>";
				}
				$this->rh->debug->trace("<b><a href=\"#\" onclick=\"debug_popup('".$out."', this); return false;\">QUERY</a>".($limit == 1 ? " ONE: " : ": ")."</b> ".$sql, 'q');
			}
			else
			{
				$this->rh->debug->trace("<b>QUERY".($limit == 1 ? " ONE: " : ": ")."</b> ".$sql, 'q');
			}			
		}
		else
		{
			$this->rh->debug->trace("<b>QUERY".($limit == 1 ? " ONE: " : ": ")."</b> ".$sql, 'q');
		}
		
		return $data;
	}

	function _Error($error_msg)
	{
		$error_msg = "DBAL [" . $this->rh->db_al . "] Error: " . $error_msg;
		if ($this->rh->debug)
			echo '<hr>' . $error_msg;
		//$this->rh->Error($error_msg);
		else
		{
			ob_end_clean();
			die($error_msg);
		}

	}

	function QueryOne($sql)
	{
		// #1. launch Query
		$res = $this->Query($sql, 1);
		// #2. get 1st
		if (sizeof($res))
			return $res[0];
		else
			return false;
	}

	function Insert($sql)
	{
		$res = $this->lowlevel->Query($sql);
		return $this->lowlevel->InsertId();
	}

	function AffectedRows()
	{
		return $this->lowlevel->AffectedRows();
	}

	// Возврат кол-ва записей в запросе
	function RecordCount($sql)
	{
		// ????? take a look on regexp pls.
		$sql = preg_replace("/^select.*?((\s|\().*?(\s|\)))from/i", "select count(*) as _row_number from ", $sql);
		$res = $this->QueryOne($sql);
		if ($res)
			return $res["_row_number"];
		else
			return 0;
	}

	/**
	 *  выполняет запрос, запоминает ссылку на результат
	 *  подразумевается для использования getRow, getObject, getArray
	 */
	function Execute($sql, $limit = 0, $offset = 0)
	{
		//типа такой плейсхолдер
		$sql = str_replace("??", $this->prefix, $sql);

		$this->handle = $this->lowlevel->Query($sql, $limit, $offset);

		if ($this->handle)
		{

			$this->numRows = $this->lowlevel->getNumRows($this->handle);
			$this->currentRow = 0;
			return $this->numRows; //$this->handle;
		}

		return null;
	}

	/**
	 * возвращает строчку объектом нужного класса
	 */
	function getObject($class_name = null)
	{
		if ($this->handle && $this->currentRow < $this->numRows)
		{
			$ret = $this->lowlevel->FetchObject($this->handle, $class_name);
			$this->currentRow++;
		} else
		{
			$this->lowlevel->FreeResult($this->handle);
			$ret = null;
		}
		return $ret;
	}

	/**
	 * возвращает строчку массивом
	 */
	function getRow()
	{
		if ($this->handle && $this->currentRow < $this->numRows)
		{
			$ret = $this->lowlevel->FetchAssoc($this->handle);
			$this->currentRow++;
		} else
		{
			$this->lowlevel->FreeResult($this->handle);
			$ret = null;
		}
		return $ret;
	}

	/**
	 * возвращает все строчки результата массом
	 * вообще-то хак для билдера
	 */
	function getArray()
	{
		//var_dump($this->handle);  

		if ($this->handle)
		{
			while ($row = $this->getRow())
			{
				//echo '<hr>';
				// var_dump($row);  
				$ret[] = $row;
			}

			return $ret;
		}
	}

	/**
	 * for osb
	 */
	function insert_id()
	{
		return $this->lowlevel->InsertId();
	}

	function SelectLimit($sql, $limit, $offset)
	{
		return $this->execute($sql, $limit, $offset);
	}
	// EOC{ DBAL } 
}
?>