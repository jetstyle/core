<?php
/*
 * Controller
 *
 */

abstract class Controller implements ArrayAccess
{
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
		Finder::useClass('routers/CommandLineRouter');
		$clr = new CommandLineRouter();
		$router = &Router::getInstance();
		$controller = &$router->find(array('class' => 'Response'), array($clr));

		if ($controller)
		{
			$controller->setParams(array('404'));
			$controller->handle();
			$tpl = &Locator::get('tpl');
			$tpl->parseSiteMap($controller->getSiteMap());
			$result = $tpl->get('html');
		}
		else
		{
			$result = '404';
		}

		echo $result;
		die();
	}

	public static function deny()
	{
		Finder::useClass('routers/CommandLineRouter');
		$clr = new CommandLineRouter();
		$router = &Router::getInstance();
		$controller = &$router->find(array('class' => 'Response'), array($clr));

		if ($controller)
		{
			$controller->setParams(array('403'));
			$controller->handle();
			$tpl = &Locator::get('tpl');
			$tpl->parseSiteMap($controller->getSiteMap());
			$result = $tpl->get('html');
		}
		else
		{
			$result = '403';
		}

		echo $result;
		die();
	}

	public static function redirect($url="")
	{
		if (empty($url))
		    $url = RequestInfo::$baseFull . RequestInfo::$pageUrl;

		if (strpos($url, "http://") !== 0 && strpos($url, "https://") !== 0)
			$url = RequestInfo::$hostProt . $url;

		header("Location: $url");
		exit;
	}

	public function __construct()
	{
		$className = get_class($this);
		$this->loadConfig($className);
	}
	
	public function loadConfig( $fileName )
	{
		$ymlFile = Finder::findScript('classes/controllers', $fileName, 0, 1, 'yml');

		if ( $ymlFile )
		{
			$ymlConfig = YamlWrapper::load($ymlFile);

			if (!is_array($ymlConfig) || empty($ymlConfig))
			{
				return false;
			}
			
			$this->params_map = $ymlConfig;

			return true;
		}

		return false;
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
			Finder::useClass('Inflector');
			$className = Inflector::underscore(str_replace("Controller", "", get_class($this)));
			$methodName = str_replace('handle_', '', $this->method);

                        if ( $methodName && !in_array($methodName, array('index', 'default')) )
			{
				$this->siteMap = strtolower($className.'/'.$methodName);
			}
			else
			{
				$this->siteMap = strtolower($className);
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

		$matches = array();

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
									break;
								}
							}
							else
							{
								$this->method = 'handle_'.$action;
								break;
							}
						}
						$matches = array();
					}
				}

				if ($this->method)
				{
					break;
				}
			}
		}

		//QUICKSTART-790
		$this->params_mapped = $matches;
		$this->preHandle( $matches );

		if ($this->method)
		{
                        
			if ($controller)
			{
				$status = call_user_func_array(
					array(&$controller, $this->method),
					array($matches)
				);
			}
			else
			{
				$status = call_user_func_array(
					array(&$this, $this->method),
					array($matches)
				);
			}
		}
                else if ( $this->params_map!==NULL && Config::get('enable_strict_404') )
                {
                         Controller::_404();
                }

		$this->postHandle( $matches );

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

	public function addToBread($title, $path = '')
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
		}

		return $ret;
	}

	protected function preHandle($config)
	{

	}

	/**
	 * TODO:
	 *  - save to cache
	 *  - extract it, контроллеру не надо делать работу View
	 */
	protected function postHandle($config)
	{
		//set colors
		$config = Config::getAll();
		$colors = array();
		foreach ($config as $name => $value)
		{
                /*
			if (strpos($name, 'colors_') === 0)
			{
				$colors[str_replace('colors_', '', $name)] = $value;
			}
                        */
			if (strpos($name, 'grid_') === 0)
			{
				$grid[str_replace('grid_', '', $name)] = $value;
			}
		}
                
                //var_dump($config["scheme_id"]);

		$view = array("colors"=> $config["scheme_id"],
		              "grid"  => $grid,
                              "header_bg_repeat" => Config::get('header_bg_repeat'));

                Finder::useClass('FileManager');

                //$view['logo'] = FileManager::getFile('Config/config:logo/small', 1);
                //$view['header_bg'] = FileManager::getFile('Config/config:bg', 1);

		$view["config_title"] = $config['project_title'];

		Locator::get('tpl')->set('View', $view);
	}

	
	/**
	 * TODO:
	 *  - extract to view
	 */
	protected function rend()
	{

		//this breaks params for non-Content controllers
		//if (!empty($this->data))
		{
			$this->data['params'] = $this->params_mapped;
			Locator::get('tpl')->set('PAGE', $this->data);
		}
        Locator::get('tpl')->set('prp', Locator::get('principal')->getUserData() );
	}

	protected function updateMeta($meta)
	{
		if (empty($this->data) || (!is_array($meta) && !($meta instanceof DBModel) ))
		{
			return;
		}

		foreach (array('meta_description', 'meta_keywords', 'meta_title') AS $key)
		{
			if ($key)
			{
				$this->data[$key] = $meta[$key];
			}
		}
	}

	public function getActionName($param)
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
		if (empty($cls) || $cls=="default")
		{
                        //$cls = $this["controller"];
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

                                                $actionParams = $this->getActionParams($v);
                                               
						foreach ($actionParams AS $fieldName => $regExp)
						{
							if ( (is_array($item) || is_object($item))  && isset($item[$fieldName]))
							{
								$pathParts[] = $item[$fieldName];
								//echo '<hr>'.$fieldName.'='.$item[$fieldName];
							}
							else
							{
								$fieldNameParts = explode('_', $fieldName);
								if (count($fieldNameParts) > 1 )
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
                                                                else if ( $item == $fieldNameParts[0] )
                                                                {
                                                                        $pathParts[] = $item;
                                                                }
							}
						}
						$result = implode('/', $pathParts);
						break;
					}
				}
                                //var_dump($result);
                                //die();
			}
		}
                else if ( $cls !==null  )
                {
                    $pathParts[] = $this->path;
                    if (is_array($this->params_map) && !empty($this->params_map))
                    {
                        foreach ($this->params_map AS $v)
                        {
                             if ($this->getActionName($v) == $cls)
                             {
                                $pathParts[] = $cls;
                             }
                        }
                        $result = implode('/', $pathParts);
                    }
                }

		return $result;
	}
        
        function getPath()
        {
            return $this->path;
        }
        
        function getParamsMap()
        {
            return $this->params_map;
        }
}
?>
