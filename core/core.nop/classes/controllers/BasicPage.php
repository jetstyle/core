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
	 *  nop: use ClassNamePage::method
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
		$ret = false;
		if (is_array($pattern))
		{
			foreach ($pattern as $k=>$p)
			{
				//� ������� $params ��������� ���������
				if (!isset($params[$i]))
				{
				    $ret = false;
				    return $ret;
				}
				$value = $params[$i];
				if ($this->_match_pattern($k, $p, $value))
				{
					$matches[$k] = $value;
				}
				else
				{
					$ret = false;
					return $ret;
				}
				$i++;
			}
			$ret = true;
		}
		elseif (empty($pattern))
		{
			$matches = $params;
			$ret = true;
			break;
		}

		return $ret;
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

	function initialize(&$ctx, $config=NULL)
	{
		$parent_status = parent::initialize($ctx, $config);

		if (isset($config['plugins']))
		config_replace($this, 'plugins', $this->config['plugins']);
		if (isset($config['_path']))
		config_replace($this, 'path', $this->config['_path'] .'/');

		return $parent_status && True;
	}

	function pre_handle()
	{

	}

	function handle()
	{
		$status = True;

		if ($this->rh->db)
		{
			$this->loadPlugins();
		}

		if (is_array($this->params_map))
		{
			foreach ($this->params_map as $v)
			{
				$this->pre_handle();

				$action = $this->getActionName($v);
				array_shift($v);

				if (count($v) > 0)
				{
					foreach ($v AS $pattern)
					{
						$matches = array();
						if (True === $this->_match_url($this->rh->params, $pattern, &$matches))
						{
						    //echo '<hr>';
						    //var_dump($matches);
						    
							if (isset($pattern[0]) && $pattern[0] === null )
							{
								$matches = array();
							}

							$action_parts = explode("::", $action);
		                    //������-������ �� ������ �����
							if (count($action_parts)==2)
							{
								$this->rh->UseClass('controllers/'.$action_parts[0]);
								$controller = new $action_parts[0];
		
								$method = 'handle_'.$action_parts[1];
								if (method_exists($controller, $method))
								{
									$this->method = $method;
									$controller->initialize($this->rh);
									$status = call_user_func_array(
										array(&$controller, $method),
										array($matches)
									);
									return $status;
								}
							}
							else
							{
								$this->method = $action;

								$status = call_user_func_array(
									array(&$this, 'handle_'.$action),
									array($matches)
								);
								return $status;
							}
						}
					}
				}
			}
		}

		return $status;
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
			$this->loadPlugin($name, $config);
		}
	}

	function &loadPlugin($name, $config)
	{
		$aspect = NULL;
		if (array_key_exists('__aspect', $config))
		{
			$aspect = $config['__aspect'];
		}

		unset($o);
		$o =& $this->rh->useModule($name);
		if (empty($o))
		{
			$this->rh->useClass('plugins/'.$name.'/'.$name);
			$o =& new $name();
		}
		$config['factory'] =& $this;
		$o->initialize($this->rh, $config);
		$this->o_plugins[] =& $o;
		if ($aspect) $this->o_aspects[$aspect] =& $o;
		return $o;
	}

	function &getAspect($name)
	{
		$o =& $this->o_aspects[$name];
		return $o;
	}

	function rend()
	{
		// HACK: ��������� ��� �������� -- �.� �����
		// lucky@npj ��� �� ����������� � ����
		// 'name' ���������� �������� ������� ��� title �������
		// (������� �� ���������)
		$this->rh->tpl->set('PAGE', $this->config);
		$this->rh->tpl->set('name', $this->title);
		if(!$this->rh->tpl->get('meta_keywords'))
		{
			$this->rh->tpl->set('meta_keywords', $this->meta_keywords);
		}
		if(!$this->rh->tpl->get('meta_description'))
		{
			$this->rh->tpl->set('meta_description', $this->meta_description);
		}
		$this->notifyOnRend();
	}

    /**
     *  �������� ��� ������ �� 
     *	   array('item',      *  ���� ��
     *     array('item'=>2, 
     */	
	private function getActionName($param)
	{
	    $keys = array_keys($param);
	    if (!is_numeric($keys[0]))
	        $ret = $keys[0];
	    else
    	    $ret = $param[0];

	    return $ret;
	}
	
	//�������� ��������� ���������� � �����
	private function getActionParams($param)
	{
	    $keys = array_keys($param);
	    if (!is_numeric($keys[0]))
	    {
	        $ret = $param[ $param[ $keys[0] ] ];
	    }
	    else
    	    $ret = $param[1];
    	
    	return $ret;
	}

    /**
     * �� ��������� url_to �������� ������� � params_map
     */
	function url_to($cls=NULL, $item=NULL)
	{
		$result = '';
				
		if (empty($cls))
		{
			$result = rtrim($this->path, '/');
		}
		else if (null !== $cls && null !== $item)
		{
			if (is_array($this->params_map) && !empty($this->params_map))
			{
				foreach ($this->params_map AS $v)
				{
					if ($this->getActionName($v) == $cls)
					{
						$pathParts = array(rtrim($this->path, '/'));

						foreach ($this->getActionParams($v) AS $fieldName => $regExp)
						{
							if (isset($item[$fieldName]))
							{
								$pathParts[] = $item[$fieldName];
//								echo '<br>'.$fieldName.'='.$item[$fieldName];
							}
							else
							{
								$fieldNameParts = explode('_', $fieldName);
//								var_dump($fieldNameParts);
								if (count($fieldNameParts) > 1)
								{
									$value = &$item;
									foreach ($fieldNameParts AS $fieldNamePart)
									{
										if (isset($value[$fieldNamePart]))
										{
											$value = &$value[$fieldNamePart];
										}
										// TODO: remove HACK
										else if (isset($value[0]) && isset($value[0][$fieldNamePart]))
										{
											$value = &$value[0][$fieldNamePart];
										}
										else
										{
											$value = null;
											break;
										}
									}
									
									if (null !== $value)
									{
										$pathParts[] = $value;
									}
								}
							}
						}
						$result = implode('/', $pathParts);
						break;
					}
				}
			}
		}
		
		return $result;
	}
}

?>
