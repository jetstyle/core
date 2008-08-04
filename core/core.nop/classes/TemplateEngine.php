<?php
/**
 * Шаблонный движок
 *
 */

define ("TPL_APPEND", 1);
define ("TPL_MODE_CLEAN",    0);
define ("TPL_MODE_COMMENTS", 1);
define ("TPL_MODE_TEXT",     2);
define ("TPL_COMPILE_NEVER",  0);
define ("TPL_COMPILE_SMART",  1);
define ("TPL_COMPILE_ALWAYS", 2);

class TemplateEngine extends ConfigProcessor
{
	public $domain = array();
	
	protected $rh;
	
	protected $compiler = null;
	
	protected $stack = array();
	
	protected $parseCache = array();
	protected $parseFunctionsCache = array();
	/**
	 * Массив соответсвий шаблон => имя закешированной функции
	 * 
	 * ex.:
	 * array(
	 * 		'news/item.html' => 'news_item_html',
	 * 		'news/feed.html' => 'news_feed_html',
	 * );
	 *
	 * @var array
	 */
	protected $files2functions = array();
	protected $skinNames = array();
	protected $skinName = '';
	protected $skinDir = '';
	
	protected $siteMap = array();
	protected $siteMapFilename = 'site_map.yml';
	
	// ############################################## //
	
	public function __construct( &$rh )
	{
		$this->rh = &$rh;

		// изначальный стек шкур на основе стека RH
		$this->DIRS = $rh->DIRS;
		unset($this->DIRS[0]);
		
		// выбрать шкуру
		$this->skin( $rh->tpl_skin );
	}

	public function &getRh()
	{
		return $this->rh;
	}
	
	public function get( $key ) // -- получить значение (намеренно без ссылки)
	{ return isset($this->domain[$key]) ? $this->domain[$key] : "" ; }

	public function set( $key, $value=1 )  // -- установить значение ключу
	{ $this->domain[$key] = $value; }

	public function setRef( $key, &$ref )  // -- установить значение ссылкой
	{ $this->domain[$key] = &$ref; }

	public function append( $key, $value ) // -- дописать в конец
	{ $this->domain[$key] .= $value; }

	public function is( $key ) // -- true, если этот ключ хоть что-то значит
	{ return isset( $this->domain[$key] ); }

	public function addToStack(&$data)
	{
		if (is_array($data))
		{
			$stackId = count($this->stack);
			$this->stack[$stackId] = array();
			
			foreach ($data AS $name => &$value)
			{
				$this->stack[$stackId][$name] = $this->domain[$name];
				if ($value{0} == '@')
				{
					$this->domain[$name] = $this->parse(substr($value, 1));
				}
				else
				{
					$this->domain[$name] = &$value;
				}
			}
			
			return $stackId;
		}
		else
		{
			return null;
		}
	}
	
	public function freeStack($stackId)
	{
		if (is_array($this->stack[$stackId]))
		{
			foreach ($this->stack[$stackId] AS $name => &$value)
			{
				$this->domain[$name] = &$value;
			}
			unset($this->stack[$stackId]);
		}
	}
	
	/**
	 * Очистка домена
	 *
	 * @param string $key
	 */
	public function free( $key = "" )
	{ 
		if ($key === "") 
		{
			$this->domain = array();
		}
		elseif( is_array($key) )
		{
			foreach($key AS $k)
			{
				unset( $this->domain[$k] );
			}
		} 
		else 
		{
			unset( $this->domain[$key] );
		}
	}

	/**
	 * Загрузка массива в ключи
	 *
	 * @param array $domain
	 */
	public function load( $domain )
	{
		if (is_array($domain))
		{
			foreach($domain AS $k => $v)
			{
				$this->set( $k, $v );
			}
		}
	}

	/**
	 * Имя текущей шкуры
	 *
	 * @return string
	 */
	public function getSkinName()
	{
		return $this->skinName;
	}
	
	/**
	 * Установить шкуру
	 *
	 * @param string $skinName
	 */
	public function skin( $skinName="" )
	{
		// запомнить каталог для FindScript
		$this->skinDir = $this->rh->tpl_root_dir.$skinName;
		if (substr($this->skinDir, -1) != "/") $this->skinDir.="/";
				
		array_unshift($this->DIRS, $this->skinDir);
		// запомнить имя шкуры
		$this->skinNames[] = $skinName;
		$this->skinName = $skinName;
		
		$this->set( "skin", $this->skinDir );
		
		$tplRootHref = $this->rh->tpl_root_href.$skinName;
		if (substr($tplRootHref, -1) != "/") $tplRootHref.="/";
		
		foreach($this->rh->tpl_skin_dirs AS $k => $dir)
		{
			$this->set( $dir, $tplRootHref.$dir."/");
		}
		
		$this->loadSiteMap();
	}

	/**
	 * Вернуться к предыдущей шкуре
	 *
	 * @return unknown
	 */
	public function unSkin()
	{
		array_shift( $this->DIRS );
		array_pop( $this->skinNames );
		return $this->setSkin( $this->skinNames[ count($this->skinNames) -1 ] );
	}

	/**
	 * Получение информации о шаблоне
	 *
	 * array(
	 * 	'tpl' => имя шаблона
	 * 	'subtpl' => имя подшаблона, если есть
	 *  'file_source' => путь до файла
	 *  'cache_name' => имя, используемое для кэша
	 * );
	 * 
	 * @param string $tplName
	 * @return array
	 */
	public function getTplInfo($tplName)
	{
		$result = array();
		
		$a = explode( ":", $tplName );

		if (sizeof($a) > 1) 
		{
			$result['subtpl'] = $a[1]; // имя подшаблона
		}
		
		$_pos = strrpos($tplName, ".");
		$result['tpl'] = $_pos ? substr($a[0], 0, $_pos) : $a[0];
		
		$result['file_source'] = $this->findTemplate( $result['tpl'] );
		$result['cache_name'] = preg_replace("/[^\w\x7F-\xFF\s]/", $this->rh->tpl_template_sepfix, str_replace($this->rh->project_dir, '', $result['file_source']));
		
		return $result;
	}
	
	public function getSiteMap()
	{
		return $this->siteMap;
	}
	
	/**
	 * Получение информации о плагине
	 *
	 * @param string $actionName
	 * @return array
	 */
	public function getActionInfo($actionName)
	{
		$result = array();
		$result['name'] = $actionName;
		$result['cache_name'] = str_replace("/", "__", $actionName);
		return $result;
	}
	
	/**
	 * Получение имени функции по кэшированному имени и имени подшаблона
	 *
	 * @param string $cacheName
	 * @param string $subTpl
	 * @return string
	 */
	public function getFuncName($cacheName, $subTpl = '')
	{
		return $this->rh->tpl_template_prefix . $this->getSkinName() . $this->rh->tpl_template_sepfix . $cacheName . $this->rh->tpl_template_sepfix . $subTpl;
	}
	
	/**
	 * Получение имени функции по кэшированному имени
	 *
	 * @param string $cacheName
	 * @return string
	 */
	public function getActionFuncName($cacheName)
	{
		return $this->rh->tpl_action_prefix . $this->getSkinName() . $this->rh->tpl_template_sepfix . $cacheName;
	}
	
	
	/**
	 * Поиск шаблона
	 *
	 * @param string $file
	 * @return string
	 */
	public function findTemplate( $file )
	{
		return parent::findScript_( "templates", $file, 0, 1, "html" );
	}
	
	/**
	 * Отпарсить контент, данный как параметр (а не брать из файла)
	 *
	 * @param string $templateContent
	 * @return string
	 */
	public function parseInstant( $templateContent )
	{
		$this->spawnCompiler();
		return $this->compiler->templateCompile( $templateContent, true );
	}

	/**
	 * Отпарсить массив
	 * Все шаблоны и подшаблоны складываются в один закэшированный файл 
	 * 
	 * @param string $siteMapKey
	 */
	public function parseSiteMap($siteMapKey)
	{
		if (!isset($this->siteMap[$siteMapKey]))
		{
			throw new TplException('Sitemap "'.$siteMapKey.'" not found');
		}
		
		$data = $this->siteMap[$siteMapKey];
		if (!$data['html'])
		{
			$data['html'] = '@html.html';
		}
		
		$cacheName = preg_replace("/[^\w\x7F-\xFF\s]/", $this->rh->tpl_template_sepfix, $siteMapKey);
		$fileCached = $this->rh->cache_dir . $this->rh->environment . $this->rh->tpl_template_sepfix . $this->skinName . $this->rh->tpl_template_file_prefix .	$cacheName . ".php";
		
		if (!file_exists($fileCached))
		{
			$this->spawnCompiler();
			$this->compiler->compileSiteMap($data, $fileCached);
		}
		
		include_once($fileCached);
		// шаблоны, из которых собран кэш
		$funcName = $this->getFuncName('site_map_system', '__get_used_files');
		$files = $funcName($this);

		// плагины, из которых собран кэш
		$funcName = $this->getFuncName('site_map_system', '__get_used_actions');
		$actions = $funcName($this);
		
		// сопоставления шаблон => функция
		$funcName = $this->getFuncName('site_map_system', '__get_files2functions');
		$this->files2functions = $funcName($this);
		
		if (is_array($files))
		{
			$recompile = $this->rh->tpl_compile != TPL_COMPILE_NEVER;
			
			if ($recompile)
			{
				$cachemtime = @filemtime($fileCached);
				$_recompile = false;
				foreach ($files AS $file => $v)
				{
					$source = $this->findTemplate($file);
					if (@filemtime($source) > $cachemtime)
					{
						$_recompile = true;
						break;
					}
				}
				
				if (is_array($actions) && !$_recompile)
				{
					foreach ($actions AS $action => $v)
					{
						$source = $this->findScript_( "plugins", $action);
						if (@filemtime($source) > $cachemtime)
						{
							$_recompile = true;
							break;
						}
					}
				}
				
				if ($_recompile)
				{
					$recompile = true;
				}
				else
				{
					$recompile = false;
				}
				
				if ($recompile)
				{
					$this->spawnCompiler();
					$this->compiler->compileSiteMap($data, $fileCached);
				}
			}

			$this->parseCache = $files;
		}
				
		if (is_array($data))
		{
			foreach ($data AS $k => $v)
			{
				if ($v{0} == '@')
				{
					
					$this->parse(substr($v, 1), $k);
				}
				else
				{
					$this->set($k, $v);
				}
			}
		}
	}
	
	/**
	 * Парсим шаблон и  возвращаем результат
	 *
	 * @param string $tplName
	 * @param string $storeTo
	 * @param boolean $append
	 * @param string $dummy
	 * @return string
	 */
	public function parse( $tplName, $storeTo="", $append = false, $dummy = "" )
	{
		if (isset($this->files2functions[$tplName]))
		{
			 $funcName = &$this->files2functions[$tplName];
			 $this->parseFunctionsCache[$funcName] = true;
		}
		else
		{		
			$tplInfo = $this->getTplInfo($tplName);
			$funcName = $this->getFuncName($tplInfo['cache_name'], $tplInfo['subtpl']);
			
			if (!isset($this->parseFunctionsCache[$funcName]))
			{
				if (!function_exists ($funcName))
				{
					if (!isset($this->parseCache[$tplInfo['tpl']]))
					{
						// получение имён файлов исходника и компилята
						$fileCached = $this->rh->cache_dir . $this->rh->environment . $this->rh->tpl_template_sepfix . $this->skinName . $this->rh->tpl_template_file_prefix .	$tplInfo['cache_name'] . ".php";
					
						// 3. проверка наличия в кэше/необходимости рекомпиляции
						$recompile = $this->rh->tpl_compile != TPL_COMPILE_NEVER;
						$recompile = $recompile || !file_exists( $fileCached );
							
						if ($recompile && $tplInfo['file_source'] && ($this->rh->tpl_compile != TPL_COMPILE_ALWAYS) && @filemtime($fileCached) >= @filemtime($tplInfo['file_source']))
						{		 
							$recompile = false;
						}
							
						// 4. перекомпиляция
						if ($recompile)
						{
							$this->spawnCompiler();
							$this->compiler->compile($tplInfo, $fileCached);
						}
										
						// 5. парсинг-таки
						include_once( $fileCached );
						$this->parseCache[$tplInfo['tpl']] = true;
					}
			
					if (function_exists ($funcName))
					{
						$this->parseFunctionsCache[$funcName] = true;
					}
					else
					{
						$file = file_get_contents($tplInfo['file_source']);
						$file = htmlentities($file, ENT_COMPAT, 'cp1251');
						$file = str_replace($tplInfo['subtpl'], "<span class=\"warning\">".$tplInfo['subtpl']."</span>", $file);
						$file = str_replace($tplName, "<span class=\"warning\">".$tplName."</span>", $file);
						$out = "<b>Source:</b><br /><br /><div><tt>".$tplInfo['tpl'].".html"."</tt><pre class=\"source\">".$file."</pre></div>";
						throw new TplException("template *<b>".$tplName."</b>* not found", $out);
					}
				}
				else
				{
					$this->parseFunctionsCache[$funcName] = true;
				}
			}
		}
		
		if ($this->parseFunctionsCache[$funcName])
		{
			ob_start();
			$funcName($this);
			$res = trim(ob_get_contents());
			ob_end_clean();
		}
		
		//6. $dummy
		if( $res=='' ) $res = $dummy;

		//7. $storeTo & $append
		if( $storeTo )
		{
			if( $append )
			{
				$this->domain[ $storeTo ] .= $res;
			}
			else
			{
				$this->domain[ $storeTo ] = $res;
			}
		}
		
		return $res;
	}

	/**
	 * Выполнить плагин $actionName
	 *
	 * @param string $actionName
	 * @param array $params
	 * @return string
	 */
	public function action( $actionName, &$params )
	{
		$actionInfo = $this->getActionInfo($actionName);

		//генерируем имя функции
		$funcName = $this->getActionFuncName($actionInfo['cache_name']);

		//проверяем экшен на существование
		if( !function_exists($funcName) )
		{
			$fileCached = $this->rh->cache_dir.$this->rh->tpl_action_file_prefix.$this->rh->environment.$this->rh->tpl_template_sepfix.$this->getSkinName().$this->rh->tpl_template_sepfix.$actionInfo['cache_name'].".php";

			//проверка на необходимость компиляции
			$recompile = $this->rh->tpl_compile != TPL_COMPILE_NEVER;
			$recompile = $recompile || !file_exists( $fileCached );
			if ($recompile)
			{
				$fileSource = $this->findScript_( "plugins", $actionName);
								
				if ($fileSource && ($this->rh->tpl_compile != TPL_COMPILE_ALWAYS))
				{
					if (@filemtime($fileCached) >= @filemtime($fileSource)) 
					{
						$recompile = false;
					}
				}
			}

			//откомпилировать функцию
			if ($recompile)
			{
				$this->spawnCompiler();
				$this->compiler->actionCompile( $actionInfo, $fileCached );
			}

			//подключить функцию
			include_once( $fileCached );
		}

		//выполняем и возвращаем результат
		ob_start();
		echo $funcName( $this, $params );
		$_ = trim(ob_get_contents());
		ob_end_clean();
		return $_;
	}
	
	/**
	 * Загрузим файл карты сайта. Должен лежать в директории со скином
	 *
	 */
	protected function loadSiteMap()
	{
		if (file_exists($this->skinDir.$this->siteMapFilename))
		{
			$fileCache = new FileCache($this->skinName.'_site_map.php');
			if ($fileCache->isValid())
			{
				$data = include $fileCache->getFileName();
				$this->siteMap = unserialize($data);
			}
			else
			{
				$this->rh->useLib('spyc');
				$this->siteMap = Spyc :: YAMLLoad($this->skinDir.$this->siteMapFilename);
				$fileCache->addSource($this->skinDir.$this->siteMapFilename);
				$str = "return '".str_replace("'", "\\'", serialize($this->siteMap))."';";
				$fileCache->write($str);
			}
		}
	}
	
	/**
	 * Порождает компиляторскую часть
	 *
	 */
	protected function spawnCompiler() 
	{
		if ( null === $this->compiler)
		{
			$this->rh->useClass("TemplateEngineCompiler");
			$this->compiler = new TemplateEngineCompiler( $this->rh );
		}
	}

	/**
	 * get tpl var
	 *
	 * @param unknown_type $key
	 * @param unknown_type $d
	 * @return unknown
	 */
	public function &a($key, &$d = null)
	{
		if ($d === null)
		{
			$d = &$this->domain;
		}
		
		if (is_array($d) || $d instanceof ArrayAccess) 
		{
			if (isset($d[$key])) 
				return $d[$key]; 
			else 
				return NULL; 
		}
		return NULL;
	}
	
	// EOC{ TemplateEngine }
}

?>