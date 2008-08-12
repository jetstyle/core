<?php
/*
 * Controller
 *
 */

abstract class Controller implements ArrayAccess
{
	protected $rh = null;
	protected $plugins = array();

	private $o_plugins = array();
	private $o_aspects = array();
	
	protected $params;
	protected $url;
	protected $path;
	protected $data = array();
	
	protected $params_map = NULL;
	
	protected $method = '';
	
	protected $siteMap = '';
	
	public function __construct()
	{
		$this->rh = RequestHandler::getInstance();
	}
	
	public function offsetExists($key)
	{
		return isset($this->data[$key]);
	}
	
	public function offsetGet($key)
	{
		return $this->data[$key];
	}
	
	public function offsetSet($key, $value)
	{
		$this->data[$key] = $value;
	}
	
	public function offsetUnset($key)
	{
		unset($this->data[$key]);
	}
	
	public function getSiteMap()
	{
		if (!$this->siteMap) 
		{
			$ss = str_replace("Controller", "", get_class($this));
			$method = str_replace('handle_', '', $this->method);
			$siteMap = RequestHandler::getInstance()->tpl->getSiteMap();
            if ($method != "default" || ( $method == "default" && isset( $siteMap[ strtolower( $ss.'/'.$method ) ]  ) ))
			{
				$this->siteMap = strtolower($ss.'/'.$method);
			}
			else
			{
				$this->siteMap = strtolower($ss);
			}
		}
		
		return $this->siteMap;
	}
	
	public function setUrl($url)
	{
		$this->url = $url;
	}
	
	public function setPath($path)
	{
		$this->path = $path;
	}
	
	public function setParams($params)
	{
		$this->params = $params;
	}
	
	public function getParams()
	{
		return $this->params;
	}
	
	public function setData($data)
	{
		$this->data = $data;
	}
	
	public function handle()
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
				$action = $this->getActionName($v);
				array_shift($v);

				if (count($v) > 0)
				{
					foreach ($v AS $pattern)
					{
						$matches = array();
						if (True === $this->_match_url($this->params, $pattern, &$matches))
						{
							if (isset($pattern[0]) && $pattern[0] === null )
							{
								$matches = array();
							}

							$action_parts = explode("::", $action);
							if (count($action_parts)==2)
							{
								Finder::useClass('controllers/'.$action_parts[0]);
								$controller = new $action_parts[0];

								$method = 'handle_'.$action_parts[1];
								if (method_exists($controller, $method))
								{
									$this->method = $method;
									$config = array($matches);
									break;
								}
							}
							else
							{
								$this->method = 'handle_'.$action;
								$config = array($matches);
								break;
							}
						}
					}
				}
			
				if ($this->method)
				{
					$this->preHandle();
					if ($controller)
					{
						$status = call_user_func_array(
							array(&$controller, $this->method),
							$config
						);
					}
					else
					{
						$status = call_user_func_array(
							array(&$this, $this->method),
							$config
						);
					}
					$this->postHandle();
					$this->rend();
					break;
				}
			}
		}

		return $status;
	}
	
	
	
	private function _match_pattern($name, $pattern, $value)
	{
		if (preg_match('#^'.$pattern.'$#', $value)) return True;
		return False;
	}

	private function _match_url($params, $pattern, $matches = array())
	{

		$i = 0;
		$ret = false;
		if (is_array($pattern))
		{
			foreach ($pattern as $k=>$p)
			{
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

	protected function &getAspect($name)
	{
		$o =& $this->o_aspects[$name];
		return $o;
	}

	protected function preHandle()
	{

	}

	protected function postHandle()
	{
		
	}

	private function loadPlugins()
	{
		if (is_array($this->plugins))
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
	}

	private function &loadPlugin($name, $config)
	{
		$aspect = NULL;
		if (array_key_exists('__aspect', $config))
		{
			$aspect = $config['__aspect'];
		}

		unset($o);

		Finder::useClass('plugins/'.$name.'/'.$name);
		$o =& new $name();
		$config['factory'] =& $this;
		$o->initialize($config);
		$this->o_plugins[] =& $o;
		if ($aspect) $this->o_aspects[$aspect] =& $o;
		return $o;
	}

	protected function rend()
	{
		if (is_array($this->o_plugins))
		{
			foreach ($this->o_plugins AS &$plugin)
			{
				$plugin->rend();
			}
		}
		
		if (!empty($this->data))
		{
			$this->rh->tpl->set('PAGE', $this->data);
		}
	}


	private function getActionName($param)
	{
	    $keys = array_keys($param);
	    if (!is_numeric($keys[0]))
	        $ret = $keys[0];
	    else
    	    $ret = $param[0];

	    return $ret;
	}

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

	protected function url_to($cls=NULL, $item=NULL)
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
