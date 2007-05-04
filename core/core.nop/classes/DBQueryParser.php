<?php

/**
 * �������� ������� �������
 *
 * ' AND {supertag} = ? '
 *
 * ��������:
 *	 {..} �� �������� ������
 *	 ?	  �� �������� ���������
 *
 */
class DBQueryParser extends Configurable
{

	//var $params=NULL; -- ��������� ������� (��� ����������� ������ ?)
	//var $factory=NULL; -- DBModel

	function parse($query)
	{
		$fn_alias = array(&$this, 'onAlias');
		$fn_args = array(&$this, 'onArg');
		// �������� ������ {foo} �� �����
		$this->param_idx = 0;
		$sql = preg_replace_callback('#{([^}]+)}#', $fn_alias, $query);
		// �������� ? (����� \?) �� ���������
		$sql = preg_replace_callback('#(?=[^\\\\])\\?#', $fn_args, $sql);
		// ���� ��������� ���������� ������ ��� ������ ��� ��������
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
