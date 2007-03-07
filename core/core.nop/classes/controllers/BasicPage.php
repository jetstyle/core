<?php

$this->useClass('controllers/Controller');

/**
 * ����� BasicPage - ������� ����� ��� ������� �����
 *
 * �������� ��������� ���������� � �������, � ������� ���������� �������.
 *
 * ��� ���������� ����� ������������� ��� ���� ������ ������������� �������� 
 * � ������� ��������.
 */
class BasicPage extends Controller
{
	/**
	 * ������� ��������
	 * array(
	 *   array('plugin_1_name', config),
	 *   ...
	 *   )
	 * config - array('key' => value, ....);
	 *
	 * ����������� ����� �������
	 *	  __aspect - ������ �������� ��������, � ������, �������� � ��������
	 *	  (��� ��������, ������ ������������� ��������� � ������ �������� ��������)
	 */
	var $plugins = array();


	// private:
	/**
	 * ������, ��� �������� ��������� �������-�������
	 */
	var $o_plugins = array();
	/**
	 * ������ ��� �������� �������� ��������
	 *
	 * ������� ��� ������, ������� ��������� ��������� 
	 * (�����������) � ����������� ���. ���������� ����� ������ ��� ������.
	 *
	 * ��� ��� ��������������� �� ��������� ����� ���������� � �� ��������.
	 * ��������� � ������� -- �� ����� ����� getAspect()
	 *
	 * ����� �������� ������ � �������� ��������.
	 */
	var $o_aspects = array();

	var $params;
	var $url;
	var $path;
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

	function registerObserver($event, $observer)
	{
		$this->observers[$event][] = $observer;
	}

	function notifyOnRend()
	{
		$topic = array(&$this);
		if (isset($this->observers['on_rend'])) 
		{
			foreach ($this->observers['on_rend'] as $v)
				call_user_func_array($v, $topic);
		}
	}

	function initialize()
	{
		$this->path = $this->config['_path'] .'/';
		$this->loadPlugins();
	}

	function handle()
	{
		//foreach (get_object_vars($this->rh) as $k=>$v) if (is_scalar($v)) echo "$k = $v<br>\n";
		$this->initializePlugins();
		parent::handle();
		$this->rend();
	}

	function loadPlugins()
	{
		foreach ($this->plugins as $info)
		{
			if (is_array($info)) 
			{
				list($name, $config) = $info;
			}
			else 
			{ 
				$name = $info; 
				$config = array(); 
			}
			$aspect = NULL;
			if (array_key_exists('__aspect', $config))
			{
				$aspect = $config['__aspect'];
			}

			$this->rh->useClass('plugins/'.$name.'/'.$name);
			$o =& new $name($this, $config);
			$this->o_plugins[] =& $o;
			if ($aspect) $this->o_aspects[$aspect] =& $o;
		}
	}

	function &getAspect($name)
	{
		$o =& $this->o_aspects[$name];
		if (isset($o) && !$o->initialized) $o->initialize();
		return $o;
	}

	function initializePlugins()
	{
		foreach ($this->o_plugins as $k=>$v)
			$this->o_plugins[$k]->initialize();
	}

	function rend()
	{
		// HACK: ��������� ��� �������� -- �.� �����
		// lucky@npj ��� �� ����������� � ����
		// 'name' ���������� �������� ������� ��� title �������
		// (������� �� ���������)
		$this->rh->tpl->set('name', $this->title);
		$this->notifyOnRend();
	}

	function url_to($cls=NULL, $item=NULL)
	{
		if (empty($cls)) return $this->path;
	}
}

?>
