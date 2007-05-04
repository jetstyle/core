<?php

/**
 * Помогает строить запросы
 *
 * ' AND {supertag} = ? '
 *
 * заменяет:
 *	 {..} на источник данных
 *	 ?	  на значение параметра
 *
 */
class DBQueryParser extends Configurable
{

	//var $params=NULL; -- параметры запроса (для подстановки вместо ?)
	//var $factory=NULL; -- DBModel

	function parse($query)
	{
		$fn_alias = array(&$this, 'onAlias');
		$fn_args = array(&$this, 'onArg');
		// заменяем алиасы {foo} на сорсы
		$this->param_idx = 0;
		$sql = preg_replace_callback('#{([^}]+)}#', $fn_alias, $query);
		// заменяем ? (кроме \?) на аргументы
		$sql = preg_replace_callback('#(?=[^\\\\])\\?#', $fn_args, $sql);
		// если отпарсили параметров больше или меньше чем передали
		if (count($this->params) !== $this->param_idx)
			$this->rh->debug->Error('Query compilation failed: wrong params count');
		return $sql;
	}

	function onAlias($matches)
	{
		$res = $this->factory->quoteField($matches[1]);
		return $res;
	}

	function onArg($matches)
	{
		$res = $this->factory->quote($this->params[$this->param_idx++]);
		return $res;
	}

}

?>
