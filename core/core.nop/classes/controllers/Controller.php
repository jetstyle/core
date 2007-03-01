<?php
/*
 * Parent Controller
 *
 */
class Controller 
{
	var $rh;
	
	function Controller(&$rh)
	{
		$this->rh =& $rh;
	}
	function handle() 
	{
		$this->_handle();
	}

	/**
	 * lucky@npj:
	 *
	 * �������� �������� ��� ��������� ���� �� ���-�� ��������
	 * � ��������� �������� ������ �������
	 *
	 * ��� ����� ����� ���� if-else / switch �� handle()
	 *
	 * �������� ������:
	 * $url = /page/10/02/2007
	 * $params = array(10, 02, 2007, edit);
	 *
	 * ����� ��������:
	 *	  $config = array('day' => 10, 'month' => 02, 'year' => 2007);
	 *	  ��������� ��������������� ����������
	 *
	 * .... 
	 *
	 * ������ $params_map = array(
	 *	  array('action_1', array(
	 *								 'param_1_name' => pattern_1, 
	 *								 'param_2_name' => pattern_2, 
	 *								 ...,
	 *							 )),
	 *	  ...
	 *	  )
	 *
	 *
	 * ������:
	 *
	 *	var $params_map = array(
	 *		array('comments', array(
	 *			'day' => '^\d+$',
	 *			'month' => '^\d+$',
	 *			'year' => '^\d+$',
	 *		)),
	 *		array('feed', array(
	 *			'user_id' => '^\d+$',
	 *			'action' => '^\w*$',
	 *		)),
	 *		array('blog',  NULL),
	 *	);
	 *
	 * ��������� $this->handler_comments($config)
	 *
	 *	FIXME: ������ �� MapHandler -- ��� �������� ��������. �����-�� 
	 *	��������������??
	 *
	 *	FIXME: �����-�� ������� ����������� � $config ����� ��������� GET /?foo=11&bar=22 ???
	 *
	 *	FIXME: �� ���� ����� �.�. ������� �� �������. ����� �������� ����� ����� ������ � 
	 *	��������.
	 */
	var $params_map = NULL;

	function _match_pattern($name, $pattern, $value)
	{
		if (preg_match('#'.$pattern.'#', $value)) return True;
		return False;
	}

	function _match_url($params, $pattern, $matches = array())
	{
		$i = 0;
		if (is_array($pattern)) 
		{
			foreach ($pattern as $k=>$p)
			{
				if (!isset($params[$i])) return False;
				$value = $params[$i];
				if ($this->_match_pattern($k, $p, $value))
				{
					$matches[$k] = $value;
				}
				else
				{
					return False;
				}
				$i++;
			}
			return True;
		}
		elseif (empty($pattern))
		{
			$matches = $params;
			return True;
		}

		return False;
	}

	function _handle()
	{
		if (is_array($this->params_map)) foreach ($this->params_map as $v)
		{
			list($action, $pattern) = $v;
			if (True === $this->_match_url($this->rh->params, $pattern, &$matches))
			{
				return call_user_func_array(array(&$this, 'handle_'.$action), array($matches));
			}
		}
		return NULL;
	}
	
}	
?>
