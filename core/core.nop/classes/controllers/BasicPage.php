<?php

$this->useClass('controllers/Controller');

/**
 * Класс BasicPage - базовый класс для страниц сайта
 *
 * Страница заполняет переменные в шаблоне, с заранее известными именами.
 *
 * Эти переменные можно рассматривать как зоны вывода динамического контента
 * в шаблоне страницы.
 */
class BasicPage extends Controller
{
	/**
	 * Плагины страницы
	 * array(
	 *   array('plugin_1_name', config),
	 *   ...
	 *   )
	 * config - array('key' => value, ....);
	 *
	 * специальные ключи конфига
	 *	  __aspect - плагин является аспектом, с именем, заданным в значении
	 *	  (при загрузке, плагин автоматически добавится в список аспектов страницы)
	 */
	var $plugins = array();


	// private:
	/**
	* массив, где хранятся созданные объекты-плагины
	*/
	var $o_plugins = array();
	/**
	 * массив для хранения аспектов страницы
	 *
	 * аспекты это модули, имеющие известный интерфейс
	 * (программный) и стандартное имя. реализацию можно менять как угодно.
	 *
	 * все что взаимодействует со страницей может обращаться к ее аспектам.
	 * обращение к аспекту -- по имени через getAspect()
	 *
	 * Набор аспектов входит в контракт страницы.
	 */
	var $o_aspects = array();

	var $params;
	var $url;
	var $path;
	/**
	 * lucky@npj:
	 *
	 * Пытаемся отмапить ЧПУ параметры урла во что-то понятное
	 * и автоматом вызывать нужный хендлер
	 *
	 * тем самым убрав кучу if-else / switch из handle()
	 *
	 * Исходные данные:
	 * $url = /page/10/02/2007
	 * $params = array(10, 02, 2007, edit);
	 *
	 * нужно получить:
	 *	  $config = array('day' => 10, 'month' => 02, 'year' => 2007);
	 *	  запустить соответствующий обработчик
	 *
	 * ....
	 *
	 * массив $params_map = array(
	 *	  array('action_1', array(
	 *								 'param_1_name' => pattern_1,
	 *								 'param_2_name' => pattern_2,
	 *								 ...,
	 *							 )),
	 *	  ...
	 *	  )
	 *
	 *
	 * Пример:
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
	 * запустить $this->handler_comments($config)
	 *
	 *	FIXME: похоже на MapHandler -- это подобная сущность. Можно-ли
	 *	скомбинировать??
	 *
	 *	FIXME: будет-ли полезно подмешивать в $config явные параметры GET /?foo=11&bar=22 ???
	 *
	 *	FIXME: по идее экшен м.б. ссылкой на функцию. тогда хендлеры можно будет мапить в
	 *	рантайме.
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
		$this->loadPlugins();

		if (is_array($this->params_map))
		{
			foreach ($this->params_map as $v)
			{
				$matches = array();

				$this->pre_handle();

				$action = array_shift($v);
								
				if (count($v) > 0)
				{
					foreach ($v AS $pattern)
					{
						if (True === $this->_match_url($this->rh->params, $pattern, &$matches))
						{
							$action_parts = explode("::", $action);
		
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
		// HACK: заголовок для страницы -- д.б общим
		// lucky@npj еще не определился с этим
		// 'name' переменная главного шаблона для title сраницы
		// (первого из элементов)
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
					if ($v[0] == $cls)
					{
						$pathParts = array(rtrim($this->path, '/'));
						
						foreach ($v[1] AS $fieldName => $regExp)
						{
							if (isset($item[$fieldName]))
							{
								$pathParts[] = $item[$fieldName];
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