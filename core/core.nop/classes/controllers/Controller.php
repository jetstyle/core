<?php
/*
 * Controller
 *
 */

abstract class Controller implements ArrayAccess
{	
	protected $plugins = array();

	private $o_plugins = array();
	private $o_aspects = array();
	
	private $breadItems = array();
	
	protected $params;
	protected $url;
	protected $path;
	protected $data = array();
	
	protected $params_map = NULL;
	
	protected $method = '';
	
	protected $siteMap = '';
	
	public static function _404()
	{
		Finder::useLib('http');
		Http::status(404);
		$tpl = &Locator::get('tpl');
		$tpl->parseSiteMap('404');
		echo $tpl->get('html');
		die();
	}
	
	public static function deny()
	{		
		Finder::useLib('http');
		Http::status(403);
		$tpl = &Locator::get('tpl');
		
		$retPath .= $_SERVER['HTTPS'] ? 'https://' : 'http://';
        $retPath .= $_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'];
        $retPath = urlencode($retPath);
		
		$tpl->set('retpath', $retPath);
		$tpl->parseSiteMap('forbidden');
		echo $tpl->get('html');
		die();
	}
	
	public static function redirect($url)
	{
		if (strpos($url, "http://") !== 0)
			$url = RequestInfo::$hostProt . $url;

		header("Location: $url");
		exit;
	}
	
	public function __construct()
	{
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
			$siteMap = Locator::get('tpl')->getSiteMap();

            if ($method && ($method != "default" || ( $method == "default" && isset( $siteMap[ strtolower( $ss.'/'.$method ) ]  ) )))
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

		if (!Config::get('db_disable'))
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
								$controller = &Router::findByClass($action_parts[0]);
								
								if (null === $controller)
								{
									throw new JSException('Controller::params_map: Controller "'.$action_parts[0].'" not found');
								}

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
					break;
				}
			}
		}
		
		$this->rend();

		return $status;
	}
	
	public function breadcrumbsWillRender($block)
	{
		foreach ($this->breadItems AS $r)
		{
			$block->addItem($r['path'], $r['title']);
		}
	}
	
	protected function addToBread($title, $path = '')
	{
		$this->breadItems[] = array(
			'title' => $title,
			'path' => $path
		);
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

	public function &getAspect($name)
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

	protected function &loadPlugin($name, $config)
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
			Locator::get('tpl')->set('PAGE', $this->data);
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

	public function url_to($cls=NULL, $item=NULL)
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
