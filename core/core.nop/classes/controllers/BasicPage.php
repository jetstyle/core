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
		if (!$o->initialized) $o->initialize();
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

}

?>
