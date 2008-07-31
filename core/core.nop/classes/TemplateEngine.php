<?php
/**
 * ��������� ������
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
	 * ������ ����������� ������ => ��� �������������� �������
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
	
	// ############################################## //
	
	public function __construct( &$rh )
	{
		$this->rh = &$rh;

		// ����������� ���� ���� �� ������ ����� RH
		$this->DIRS = $rh->DIRS;
		unset($this->DIRS[0]);
		
		// ������� �����
		$this->skin( $rh->tpl_skin );
	}

	public function &getRh()
	{
		return $this->rh;
	}
	
	public function get( $key ) // -- �������� �������� (��������� ��� ������)
	{ return isset($this->domain[$key]) ? $this->domain[$key] : "" ; }

	public function set( $key, $value=1 )  // -- ���������� �������� �����
	{ $this->domain[$key] = $value; }

	public function setRef( $key, &$ref )  // -- ���������� �������� �������
	{ $this->domain[$key] = &$ref; }

	public function append( $key, $value ) // -- �������� � �����
	{ $this->domain[$key] .= $value; }

	public function is( $key ) // -- true, ���� ���� ���� ���� ���-�� ������
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
	 * ������� ������
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
	 * �������� ������� � �����
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
	 * ��� ������� �����
	 *
	 * @return string
	 */
	public function getSkinName()
	{
		return $this->skinName;
	}
	
	/**
	 * ���������� �����
	 *
	 * @param string $skinName
	 */
	public function skin( $skinName="" )
	{
		// ��������� ������� ��� FindScript
		$this->skinDir = $this->rh->tpl_root_dir.$skinName;
		if (substr($this->skinDir, -1) != "/") $this->skinDir.="/";
				
		array_unshift($this->DIRS, $this->skinDir);
		// ��������� ��� �����
		$this->skinNames[] = $skinName;
		$this->skinName = $skinName;
		
		$this->set( "skin", $this->skinDir );
		
		$tplRootHref = $this->rh->tpl_root_href.$skinName;
		if (substr($tplRootHref, -1) != "/") $tplRootHref.="/";
		
		foreach($this->rh->tpl_skin_dirs AS $k => $dir)
		{
			$this->set( $dir, $tplRootHref.$dir."/");
		}
	}

	/**
	 * ��������� � ���������� �����
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
	 * ��������� ���������� � �������
	 *
	 * array(
	 * 	'tpl' => ��� �������
	 * 	'subtpl' => ��� ����������, ���� ����
	 *  'file_source' => ���� �� �����
	 *  'cache_name' => ���, ������������ ��� ����
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
			$result['subtpl'] = $a[1]; // ��� ����������
		}
		
		$_pos = strrpos($tplName, ".");
		$result['tpl'] = $_pos ? substr($a[0], 0, $_pos) : $a[0];
		
		$result['file_source'] = $this->findTemplate( $result['tpl'] );
		$result['cache_name'] = preg_replace("/[^\w\x7F-\xFF\s]/", $this->rh->tpl_template_sepfix, str_replace($this->rh->project_dir, '', $result['file_source']));
		
		return $result;
	}
	
	/**
	 * ��������� ���������� � �������
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
	 * ��������� ����� ������� �� ������������� ����� � ����� ����������
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
	 * ��������� ����� ������� �� ������������� �����
	 *
	 * @param string $cacheName
	 * @return string
	 */
	public function getActionFuncName($cacheName)
	{
		return $this->rh->tpl_action_prefix . $this->getSkinName() . $this->rh->tpl_template_sepfix . $cacheName;
	}
	
	
	/**
	 * ����� �������
	 *
	 * @param string $file
	 * @return string
	 */
	public function findTemplate( $file )
	{
		return parent::findScript_( "templates", $file, 0, 1, "html" );
	}
	
	/**
	 * ��������� �������, ������ ��� �������� (� �� ����� �� �����)
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
	 * ��������� ������
	 * ��� ������� � ���������� ������������ � ���� �������������� ���� 
	 * 
	 * ex.:
	 * $cacheName = 'news'
	 * $data = array(
	 * 		'name' => '�������',
	 * 		'HTML:body' => '@news/item.html',
	 * 		'html' => '@html.html'
	 * );
	 * 
	 * @param string $cacheName
	 * @param array $data
	 */
	public function parseSiteMap($cacheName, $data)
	{
		$cacheName = preg_replace("/[^\w\x7F-\xFF\s]/", $this->rh->tpl_template_sepfix, $cacheName);
		$fileCached = $this->rh->cache_dir . $this->rh->environment . $this->rh->tpl_template_sepfix . $this->skinName . $this->rh->tpl_template_file_prefix .	$cacheName . ".php";
		
		if (!file_exists($fileCached))
		{
			$this->spawnCompiler();
			$this->compiler->compileSiteMap($data, $fileCached);
		}
		
		include_once($fileCached);
		// �������, �� ������� ������ ���
		$funcName = $this->getFuncName('site_map_system', '__get_used_files');
		$files = $funcName($this);

		// �������, �� ������� ������ ���
		$funcName = $this->getFuncName('site_map_system', '__get_used_actions');
		$actions = $funcName($this);
		
		// ������������� ������ => �������
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
	 * ������ ������ �  ���������� ���������
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
						// ��������� ��� ������ ��������� � ���������
						$fileCached = $this->rh->cache_dir . $this->rh->environment . $this->rh->tpl_template_sepfix . $this->skinName . $this->rh->tpl_template_file_prefix .	$tplInfo['cache_name'] . ".php";
					
						// 3. �������� ������� � ����/������������� ������������
						$recompile = $this->rh->tpl_compile != TPL_COMPILE_NEVER;
						$recompile = $recompile || !file_exists( $fileCached );
							
						if ($recompile && $tplInfo['file_source'] && ($this->rh->tpl_compile != TPL_COMPILE_ALWAYS) && @filemtime($fileCached) >= @filemtime($tplInfo['file_source']))
						{		 
							$recompile = false;
						}
							
						// 4. ��������������
						if ($recompile)
						{
							$this->spawnCompiler();
							$this->compiler->compile($tplInfo, $fileCached);
						}
										
						// 5. �������-����
						include_once( $fileCached );
						$this->parseCache[$tplInfo['tpl']] = true;
					}
			
					if (function_exists ($funcName))
					{
						$this->parseFunctionsCache[$funcName] = true;
					}
					else
					{
						if (!@in_array($tplInfo['subtpl'], $this->soft_subtpls) && substr($tplInfo['subtpl'], -4) != "_sep" && substr($tplInfo['subtpl'], -6) != "_empty")
						{
							$id = ++$this->rh->exception_id;
							$out = "<br /> <p>
									<div style='background-color:#DDDDDD'>
									Func_name=<b>" . $funcName . "</b>
									<br /> Sub_tpl_name=<b>" . $tplInfo['subtpl'] . "</b>";
							$out .= "<br /> Sub_tpl_source=<b>".$tplInfo['file_source']."</b> ";
							$out .= "<a href='#' onclick='document.getElementById(\"exc_".$id."\").style.display= (document.getElementById(\"exc_".$id."\").style.display==\"\" ? \"none\" : \"\" ); document.getElementById(\"exc_".$id."\").style.backgroundColor=\"#EEEEEE\"; return false;'>(click to show)</a></div>";
							$out .= "<div style='display:none' id=\"exc_".$id."\">" . nl2br(implode("\n", file($tplInfo['file_source']))) . "</div></p>";
					
							throw new TplException($out);
						}
						else
						{
							$this->parseFunctionsCache[$funcName] = false;
						}
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
	 * ��������� ������ $actionName
	 *
	 * @param string $actionName
	 * @param array $params
	 * @return string
	 */
	public function action( $actionName, &$params )
	{
		$actionInfo = $this->getActionInfo($actionName);

		//���������� ��� �������
		$funcName = $this->getActionFuncName($actionInfo['cache_name']);

		//��������� ����� �� �������������
		if( !function_exists($funcName) )
		{
			$fileCached = $this->rh->cache_dir.$this->rh->tpl_action_file_prefix.$this->rh->environment.$this->rh->tpl_template_sepfix.$this->getSkinName().$this->rh->tpl_template_sepfix.$actionInfo['cache_name'].".php";

			//�������� �� ������������� ����������
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

			//��������������� �������
			if ($recompile)
			{
				$this->spawnCompiler();
				$this->compiler->actionCompile( $actionInfo, $fileCached );
			}

			//���������� �������
			include_once( $fileCached );
		}

		//��������� � ���������� ���������
		ob_start();
		echo $funcName( $this, $params );
		$_ = trim(ob_get_contents());
		ob_end_clean();
		return $_;
	}
	
	/**
	 * ��������� �������������� �����
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