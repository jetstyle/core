<?php
/*
	Компилятор шаблонного движка. Создаётся внутри TE, руками пользоваться не должен.
	 ---------
	
	 Необходимые параметры в $rh, описывающие правила разбора шаблонов:
	
	 $rh->tpl_prefix = "{{";
	 $rh->tpl_postfix = "}}";
	 $rh->tpl_instant = "~";
	 $rh->tpl_construct_action   = "!";    // {{!text Test}}
	 $rh->tpl_construct_action2  = "!!";   // {{!!text}}Test{{!!/text}}
	 $rh->tpl_construct_if       = "?";    // {{?var}} or {{?!var}}
	 $rh->tpl_construct_ifelse   = "?:";   // {{?:}}
	 $rh->tpl_construct_ifend    = "?/";   // {{?/}} is similar to {{/?}}
	 $rh->tpl_construct_object   = "#.";   // {{#obj.property}}
	 $rh->tpl_construct_tplt     = "TPL:"; // {{TPL:Name}}...{{/TPL:Name}}
	 $rh->tpl_construct_comment  = "#";    // <!-- # persistent comment -->
	 $rh->tpl_construct_for       = "!for";    // {{!for items do=template}}
	
	 $rh->tpl_instant_plugins = array( "dummy" );
	 // lucky:
	 $rh->tpl_construct_standard_camelCase   = True;   // $o->SomeValue(), иначе $o->some_value()
	 $rh->tpl_construct_standard_getter_prefix   = 'get';   // $o->getSomeValue(), иначе $o->SomeValue()
	
	 $rh->shortcuts = array(
	 "=>" => array("=", " typografica=1"),
	 "=<" => array("=", " strip_tags=1"),
	 "+>" => array("+", " typografica=1"),
	 "+<" => array("+", " strip_tags=1"),
	 "@" => "!include ",
	 "=" => "!text ",
	 "+" => "!message ",
	 );
 */
define ('TE_TYPE', 0);
define ('TE_VALUE', 1);

define ('TE_TYPE_STRING', 0);
define ('TE_TYPE_TEMPLATE', 1);
define ('TE_TYPE_VARIABLE', 2);
define ('TE_TYPE_PHP_SCRIPT', 3);

class TemplateEngineCompiler
{
	protected $rh;
	protected $tpl;
	
	/**
	 * Скомпилированные функции шаблонов
	 *
	 * @var array
	 */
	protected $compiledFunctions = array();
	
	/**
	 * Скомпилированные функции плагинов
	 *
	 * @var array
	 */
	protected $compiledActionFunctions = array();
	
	/**
	 * Компилировать шаблон вместе со всеми его зависимостями
	 *
	 * @var boolean
	 */
	protected $compileWithExternals = false;
	
	/**
	 * Скомпилированные шаблоны
	 *
	 * @var array
	 */
	protected $compiledTemplates = array();
	
	/**
	 * Скомпилированные плагины
	 *
	 * @var array
	 */
	protected $compiledActions = array();
	
	/**
	 * Сопоставление шаблона закэшированной функции
	 *
	 * @var array
	 */
	protected $files2functions = array();
	
	/**
	 * Временное хранилище
	 * При компиляции шаблона сюда собираются имена плагинов, входящих в него 
	 *
	 * @var array
	 */
	protected $externalActions = array();
	
	/**
	 * Временное хранилище
	 * При компиляции шаблона сюда собираются имена внешних шаблонов, входящих в него 
	 *
	 * @var array
	 */
	protected $externalTemplates = array();
	
	protected $sourceFile = null;
	protected $sourceTemplate = null;
	
	public function __construct( &$rh )
	{
		$this->tpl = &$rh->tpl;
		$this->rh  = &$rh;
		// compiler meta regexp
		$this->long_regexp =
         "/(".
              "(".$this->rh->tpl_prefix.$this->rh->tpl_construct_action2.
                  "(.+?)".$this->rh->tpl_postfix.
                  ".*?".
		$this->rh->tpl_prefix."\/".$this->rh->tpl_construct_action2.
                  "(\\3)?".$this->rh->tpl_postfix.")".         // loooong standing {{!!xxx}}yyy{{/!!}}
          "|".
              "(".$this->rh->tpl_prefix.".+?".$this->rh->tpl_postfix.")". // single standing {{zzz}}
          ")/si";

		$this->single_regexp =
         "/^".$this->rh->tpl_prefix."(.+?)".$this->rh->tpl_postfix."$/ims";
		$this->double_regexp =
         "/^".$this->rh->tpl_prefix.$this->rh->tpl_construct_action2.
              "(.+?)".$this->rh->tpl_postfix.
              "(.*?)".
		$this->rh->tpl_prefix."\/".$this->rh->tpl_construct_action2.
              "(\\1)?".$this->rh->tpl_postfix."$/ims";
		// single regexps
		$this->object_regexp =
         "/^".$this->rh->tpl_construct_object{0}."([^".$this->rh->tpl_construct_object{1}."]+)".
          "[".$this->rh->tpl_construct_object{1}."](.+)$/i";
		$this->action_regexp =
         "/^".$this->rh->tpl_construct_action."(.+)$/i";
		$this->tpl_string_regexp = '/^([\'"])([^\\1]*)(\\1)$/i';
	 $this->tpl_arg_regexp = '/^'.preg_quote($this->rh->tpl_arg_prefix, '/')
	 .'(.+)'.preg_quote($this->rh->tpl_arg_postfix, '/').'$/i';

	}


	/**
	 * Компиляция контента одного шаблона
	 *
	 * @param string $content
	 * @param boolean $instant
	 * @return string
	 */
	public function templateCompile( $content, $instant = false ) 
	{
		$this->_instant = $instant;
		$content = preg_replace_callback($this->long_regexp, array( &$this, "templateCompileCallback"), $content);
		return $content;
	}
	
	/**
	 * Компиляция массива шаблонов и из зависимостей в один кэш файл
	 *
	 * ex.:
	 * $data = array(
	 * 		'HTML:body' => '@news/item.html',
	 * 		'html' => '@html.html'
	 * );
	 * 
	 * @param array $data
	 * @param string $fileCached - имя кэш файла
	 */
	public function compileSiteMap($data, $fileCached)
	{
		if (!is_array($data) || empty($data))
		{
			throw new TplException('TplCompiler: $data empty');
		}
		
		$this->compileWithExternals = true;
		
		$this->compiledFunctions = array();
		$this->compiledActionFunctions = array();
		$this->compiledTemplates = array();
		$this->compiledActions = array();
		
		try
		{
			foreach ($data AS $k => $v)
			{
				if ($v{0} == '@')
				{
					$this->compile(substr($v, 1));
				}
			}
		}
		catch (FileNotFoundException $e)
		{
//			var_dump($this->sourceFile);
//			die();
			
			$out = $e->getText().'<br />';
			
			if ($this->sourceFile)
			{
				$fn = $e->getFilename();
				$pi = pathinfo($fn);
				if ($pi['extension'] == 'php')
				{
					$fn = substr($fn, 0, strlen($fn) - 4);
				}
				
				$file = file_get_contents($this->sourceFile);
				$file = htmlentities($file, ENT_COMPAT, 'cp1251');
				$file = str_replace($fn, "<span class=\"warning\">".$fn."</span>", $file);
				$out .= "<b>Source:</b><br /><br /><div><tt>".$this->sourceTemplate."</tt><pre class=\"source\">".$file."</pre></div>";
			}
			
			throw new TplException('Compiler: '.$e->getMessage(), $out);
		}
		
		$compiledTemplates = array();
		foreach ($this->compiledTemplates AS $fileName => $v)
		{
			$fileName = substr($fileName, 0, strrpos($fileName, '.'));
			$compiledTemplates[$fileName] = true;
		}
		
		// helper functions, also store to cache
		$this->compiledFunctions[$this->tpl->getFuncName('site_map_system', '__get_files2functions')] = $this->templateBuildFunction('<'.'?php return unserialize(\''.str_replace("'", "\'", serialize($this->files2functions)).'\'); ?'.'>');
		$this->compiledFunctions[$this->tpl->getFuncName('site_map_system', '__get_used_files')] = $this->templateBuildFunction('<'.'?php return unserialize(\''.str_replace("'", "\'", serialize($compiledTemplates)).'\'); ?'.'>');
		$this->compiledFunctions[$this->tpl->getFuncName('site_map_system', '__get_used_actions')] = $this->templateBuildFunction('<'.'?php return unserialize(\''.str_replace("'", "\'", serialize($this->compiledActions)).'\'); ?'.'>');
		
		
		$this->writeToFile($fileCached, $this->compiledFunctions);
		$this->writeActionsToFile($fileCached, $this->compiledActionFunctions, 'a');
		
		$this->compileWithExternals = false;
		
		return true;
	}
	
	/**
	 * Компиляция шаблона. 
	 *
	 * @param array OR string $tplInfo (если string - то имя шаблона, иначе результат $tpl->getTplInfo())
	 * @param string $fileCached
	 * @return boolean
	 */
	public function compile($tplInfo, $fileCached = null)
	{
		if (!is_array($tplInfo))
		{
			$tplInfo = $this->tpl->getTplInfo($tplInfo);
		}
				
		$pi = pathinfo( $tplInfo['file_source'] );
		if ($pi["extension"] != "html")
		{
			throw new TplException("Compiler: not .html template found @ ".$tplInfo['file_source']."!");
		}

		if (!isset($this->compiledTemplates[$tplInfo['tpl'].'.html']))
		{
			$this->compiledTemplates[$tplInfo['tpl'].'.html'] = true;
		}
		else
		{
			return false;
		}
				
		// 1. разобрать файл на кусочки
		if (!$pieces = $this->templateRead( $tplInfo['file_source'], $tplInfo['tpl'] ))
		{
			return false;
		}
	
		if (null !== $fileCached)
		{
			$this->compiledFunctions = array();
		}
		
		// 2. скомпилировать кусочки
		foreach( $pieces AS $k=>$v )
		{
			 // объединяем одноимянные шаблоны в одну функцию if () elseif () ... else
			 // если кусок один -- тогда возвращаем без условий
			 // на выходе $fbody - тело функции
			 $fbody = '';
			 $singlePattern = False;

			 foreach ($v AS $vv)
			 {
				 $args = $vv[0];
				 $body = $vv[1];
				 if (empty($fbody) && empty($args))
				 {
					 // мы тут первый и последний раз. это {{:tpl}}
					 $fbody = $body; 
					 $singlePattern = True; break;
				 }
				 elseif (empty($fbody))
				 { // мы тут первый раз. это {{:tpl *whatsup}}
					 $fbody .= '{{?'.$args.'}}'.$body;
				 }
				 else
				 { // это {{:tpl *whatsnext}}
					 $fbody .= '{{?:'.$args.'}}'.$body;
					 if (empty($args)) break; // нет аргументов -- это безусловный шаблон
					 // шаблоны после можно пропустить
				 }
			 }
			 
			 if (!$singlePattern) 
			 {
			 	$fbody .= '{{/?}}';
			 }

			 $funcName = $this->tpl->getFuncName($tplInfo['cache_name'], ($k=="@")?"":$k);
			 $this->files2functions[$tplInfo['tpl'].'.html'.(($k=="@")?"":":".$k)] = $funcName;
			 $this->compiledFunctions[ $funcName ] = $this->templateBuildFunction( $this->templateCompile($fbody) );
		}

		if (null === $fileCached)
		{
			return $this->compiledFunctions;
		}
		else
		{
			$this->writeToFile($fileCached, $this->compiledFunctions);
		}
		
		return true;
	}

	
	/**
	 * Компиляция плагина
	 *
	 * @param array OR string $actionInfo
	 * @param string $cacheFile
	 * @return boolean
	 */
	public function actionCompile( $actionInfo, $cacheFile = null ) // -- возвращает true, если удачно
	{
		if (!is_array($actionInfo))
		{
			$actionInfo = $this->tpl->getActionInfo($actionInfo);
		}
		
		if (!isset($this->compiledActions[$actionInfo['name']]))
		{
			$this->compiledActions[$actionInfo['name']] = true;
		}
		else
		{
			return false;
		}
		
		$funcName = $this->tpl->getActionFuncName($actionInfo['cache_name']);
		
		$source = $this->tpl->findScript_( "plugins", $actionInfo['name']);
		
		$enviroment = "<"."?php \$rh=&\$tpl->getRh(); ?>";

		if (null !== $cacheFile)
		{
			$this->compiledActionFunctions = array();
		}
		
		$lines = @file($source);
		
		if (is_array($lines))
		{
			$this->compiledActionFunctions[ $funcName ] = $this->templateBuildFunction( $enviroment . preg_replace("/\/\*.*?\*\//s","",implode('', $lines)));
			
			if ($this->compileWithExternals)
			{
				if (substr($lines[1], 0, 2) == '//')
				{
					$files = explode(',', substr($lines[1], 2));
					if (is_array($files))
					{
						foreach ($files AS $file)
						{
							$this->sourceFile = $source;
							$this->sourceTemplate = $actionInfo['name'];
							if ($file{0} == '@')
							{
								$fileName = trim(substr($file, 1));
								$this->compile($fileName);
							}
							elseif($file{0} == '!')
							{
								$pluginName = trim(substr($file, 1));
								if (!isset($this->compiledActions[$pluginName]))
								{
									$this->actionCompile($pluginName);
								}
							}
						}
					}
				}
			}
		}
		else
		{
			throw new TplException('Compiler: can\'t read file '.$source);
		}
		
		if (null === $cacheFile)
		{
			return $this->compiledActionFunctions;
		}
		else
		{
			$this->writeActionsToFile($cacheFile, $this->compiledActionFunctions);
		}

		return true;
	}
	
	
	/**
	 * Разбор файла на подшаблоны и то, что находится вне подшаблонов 
	 *
	 * @param string $file_name
	 * @param string $tplName
	 * @return array (name => content)
	 */
	protected function templateRead( $fileName, $tplName )
	{
		// 0. get contents
		$contents = @file_get_contents($fileName);
		if (!$contents)
		{
			throw new TplException("Compiler: Can't read *".$fileName."*");
		}

		// {{/TPL}}
		if (preg_match( "/".$this->rh->tpl_prefix."\/".
						$this->rh->tpl_construct_tplt.
						$this->rh->tpl_postfix."/si", $contents, $matches)
			)
		{
			throw new TplException("Compiler: {{/TPL}} found!");
		}

		// B. typo correcting {{?/}} => {{/?}}
		$contents =  str_replace( $this->rh->tpl_prefix.$this->rh->tpl_construct_ifend.
		$this->rh->tpl_postfix,
		$this->rh->tpl_prefix."/".$this->rh->tpl_construct_if.
		$this->rh->tpl_postfix,
		$contents );

		// 1. strip comments
		$contents = preg_replace( "/(\s*)<!--(".
                                    "( )".
                                    "|".
                                    "( [^".$this->rh->tpl_construct_comment."].*?)".
                                    "|".
                                    "([^ ".$this->rh->tpl_construct_comment."].*?)".
                                    ")-->(\s*)/msi", 
                              "", $contents );

		// and then strip "contstructness" from comments
		$contents = preg_replace( "/(\s*)<!--( )?#(.*?)-->/msi",
                              "<!--$2$3-->", $contents );

		//zharik: protect ourselfs from '<?' in templates
		$contents = str_replace( "<?", "<<?php ; ?>?", $contents );

		// lucky: наследование шаблонов
		// {{#extends @path/to/base.html}}
		$is_extends = NULL;
		$re = $this->rh->tpl_prefix.'#extends'.'\s+@(.+?)\\.html\s*'.$this->rh->tpl_postfix;
		if (preg_match('/'. $re .'/i', $contents, $matches))
		{
			$is_extends = TRUE;
			$res = $this->templateRead($this->tpl->_findTemplate($matches[1]));
			$contents = str_replace( $matches[0], '', $contents );
		}
		else
		{
			$is_extends = FALSE;
			$res = array();
		}
		
		// собираем внешние шаблоны и плагины
		
		// replace @: with template name && compile external
		$this->_template_item = $tplName;
		$this->externalTemplates[$tplName] = array();
		$this->externalActions[$tplName] = array();
		$contents = preg_replace_callback("/(".$this->rh->tpl_prefix.')(.*?)('.$this->rh->tpl_postfix.")/si", array(&$this, 'templateReadCallback'), $contents);
		$this->_template_item = '';

		if ($this->compileWithExternals)
		{
			foreach ($this->externalTemplates[$tplName] AS $template)
			{
				$this->sourceFile = $fileName;
				$this->sourceTemplate = $tplName.'.html';
				$this->compile($template);
			}
			
			foreach ($this->externalActions[$tplName] AS $action)
			{
				$this->sourceFile = $fileName;
				$this->sourceTemplate = $tplName.'.html';
				$this->actionCompile($action);
			}
		}
		
		// 2. grep all {{TPL:..}} & replace `em by !includes
		//$include_prefix = $this->rh->tpl_prefix.$this->rh->tpl_construct_action."include ";
		$stack     = array( $contents );
		$stackname = array( "@" );
		$stackargs = array( '' );
		$stackpos = 0;

		/* ru@jetstyle чтобы шаблоны можно было метить облегчённо */
		$tpl_construct_tplt_re = '('.$this->rh->tpl_construct_tplt.'|'.$this->rh->tpl_construct_tplt2.')';
		//разрезаем на подшаблоны
		while ($stackpos < sizeof($stack) )
		{
			$data = $stack[$stackpos];
			$c =preg_match_all( "/".$this->rh->tpl_prefix.
			/*$this->rh->tpl_construct_tplt*/
			//$tpl_construct_tplt_re."([A-Za-z0-9_]+)".
			$tpl_construct_tplt_re.'([A-Za-z0-9_]+)(?:\s+(.+?))?'.
			$this->rh->tpl_postfix."(.*?)".
			$this->rh->tpl_prefix."\/".
			/*$this->rh->tpl_construct_tplt*/"\\1\\2".
			$this->rh->tpl_postfix."/si",
			$data, $matches, PREG_SET_ORDER  );

			foreach( $matches as $match )
			{
				//$sub = $tpl_name.".html:".$match[1];
				//$data = str_replace( $match[0], $include_prefix.$sub.$this->rh->tpl_postfix, $data );
				$data = str_replace( $match[0], '', $data ); // ru@jetstyle

				$stack[]     = $match[4]; // контент внутри шаблона
				$stackname[] = $match[2]; // имя шаблона
				$stackargs[] = $match[3]; // аргументы шаблона
				// lucky: наследование: override'им родительские определения подшаблонов
				if ($is_extends /*&& isset($res[$match[2]])*/) {
					unset($res[$match[2]]);
				}
			}

			$stack[$stackpos] = $data;
			$stackpos++;
		}
		
		if ($is_extends)
		{
			// lucky: что использовать в качестве основного шаблона: себя или родителя?
			//   если в шаблоне остался неразобранный текст
			//   используем себя
			if (trim($stack[0]) !== '')
			{
				unset($res['@']);
			}
			else
			{
				// иначе используем родителя $res['@'] (себя херим)
				unset($stack[0], $stackname[0], $stackargs[0]);
			}
		}

		// pack results
		foreach( $stack as $k=>$v )
		{
			$res[ $stackname[$k] ][] = array($stackargs[$k], $v);
		}

		return $res;
	}

	protected function templateReadCallback($matches)
	{
		$tplName = $this->_template_item;
		
		if ($this->compileWithExternals)
		{
			preg_match_all('/.*?@([\w]+[\w\/\._]+).*?/si', $matches[2], $matches1);
			if (is_array($matches1[1]))
			{
				foreach ($matches1[1] AS $m1)
				{
					$this->externalTemplates[$tplName][$m1] = $m1;
				}
			}
			
			$action = '';
			
			if ($matches[2]{0} == '@')
			{
				$this->externalActions[$tplName]['include'] = 'include';
			}
			else
			{
				preg_match('/^(!|!!)([\w_]+).*?/si', $matches[2], $matches1);
				if (!empty($matches1[2]))
				{
					$this->externalActions[$tplName][$matches1[2]] = $matches1[2];
				}
			}
		}
				
		$matches[2] = preg_replace('#(.*?@)(:)([\w]+.*?)#i', "$1".$tplName.".html:$3", $matches[2]);		
		return $matches[1].$matches[2].$matches[3];
	}
	
	
	protected function applyShortcuts($thing)
	{
		foreach( $this->rh->shortcuts AS $shortcut=>$replacement )
		{
			if (strpos($thing, $shortcut) === 0)
			{
				if (!is_array($replacement)) $replacement = array($replacement, "");
				$thing = $replacement[0]. substr($thing, strlen($shortcut)). $replacement[1];
			}
		}
		
		return $thing;
	}
	
	protected function templateCompileCallback( $things )
	{
//		var_dump($things);
//		var_dump($this->rh->tpl_instant);
//		die();
		
		$_instant = false;

		$thing = $things[0];
		
		// is instant ?
		if ($thing{(strlen($this->rh->tpl_prefix))} == $this->rh->tpl_instant)
		{
			$thing = substr( $thing, 0, strlen($this->rh->tpl_prefix) ).
			substr( $thing, strlen($this->rh->tpl_prefix)+1 );
			$_instant = true;
		}

		// {{!!action param=value param=value value}}....{{/!!}}
		if (preg_match($this->double_regexp, $thing, $matches))
		{
			$params = $this->parseActionParams( $matches[1] );
			if (isset($params["instant"]) && $params["instant"][TE_VALUE]) $_instant = true; // {{!!typografica instant=1}}...{{/!!}} prerenders

			$result = "ob_start();?>".$this->templateCompile($matches[2])."<"."?php \n";
			$result .= ' $_='.$this->implodeActionParams( $params ).';'."\n".' $_["_"] = ob_get_contents(); ob_end_clean();'."\n";
			$result .= ' echo $tpl->Action('.$this->compileParam($params['_name']).', $_ ); ';
			$instant = $result;
		}
		else
		{
			// 1. rip {{..}}
			preg_match($this->single_regexp, $thing, $matches);
			
			$thing = preg_replace('#[\\n\\r]#', '', $matches[1]);

			// 2. shortcuts
			$thing = $this->applyShortcuts($thing);
			
			// {{?:}} // else, elseif
			if (strpos($thing, $this->rh->tpl_construct_ifelse) === 0)
			{
				$what = trim(substr($thing, 2));
				
				if ($what)
				{
					$result =  ' } elseif ('.$invert.$this->parseExpression($what).') { ';
				}
				else
				{
					$result =  ' } else { ';
				}
			}
			// {{/?}} // end of if
			elseif ($thing == "/".$this->rh->tpl_construct_if)
			{
				$result = ' } ';
			}
			// {{?var}}	// if
			elseif ($thing{0} == $this->rh->tpl_construct_if)
			{
				$result =  ' if ('.$invert.$this->parseExpression(substr($thing, 1)).') { ';
			}
			// {{!action param}}
			elseif (preg_match($this->action_regexp, $thing, $matches))
			{
				$params = $this->parseActionParams( $matches[1] );
				$pluginName = $params['_name'];
				unset($params['_name']);
				
				if (in_array($params["_name"][TE_VALUE], $this->rh->tpl_instant_plugins) || (isset($params["instant"]) && $params["instant"][TE_VALUE]))
				{
					$_instant=true;
				}

				$result = '';
				$result .= '$_ = '.$this->implodeActionParams( $params ).';';
				$result .= 'echo $tpl->action('.$this->compileParam($pluginName).', $_); ';
				$instant = $result;
			}
			// {{#obj.property}}
			// {{var}}
			// {{var|plugin}}
			else
			{
				//	pipes
				$A = array_map('trim', explode('|',$thing));
				$var = array_shift($A);
				$result = '';

				if (strlen($var) > 0)
				{
					if (count ($A) > 0)
					{
						$result .= ' $_r = &'.$this->parseExpression($var) .';'."\n";										
						foreach ($A AS $stmt)
						{
							// FIXME:
							// c&p {{!action param}}
							if (preg_match($this->action_regexp, '!'.$stmt, $matches))
							{
						 		$params = $this->parseActionParams( $matches[1] );
								$pluginName = $params['_name'];
								unset($params['_name']);
								
						  		$params['_'] = array(
						  			TE_TYPE => TE_TYPE_PHP_SCRIPT,
						  			TE_VALUE => '&$_r',
						  		);
	
						  		$result .= ' $_='.$this->implodeActionParams( $params ).';';
						  		$result .= ' $_r = $tpl->action('.$this->compileParam($pluginName).', $_ ); ';
						  		
						  		if ($this->compileWithExternals)
								{
									$this->actionCompile($pluginName[TE_VALUE]);
								}
							}
							// с&p
						}
						$result .= 'echo $_r; ';
					}
					elseif (strpos($var, '=') !== false)
					{
						$result = ' '.$this->parseExpression($var) .';'."\n";
					}
					else
					{
						$result .= ' echo '.$this->parseExpression($var) .';'."\n";
					}

					$instant = $result;
				}
			}
		}

		$tpl = &$this->tpl;
		
		if ($this->_instant || $_instant)
		{
			if ($instant)
			{ 
//				var_dump($instant);
//				die();
				ob_start();
				eval($instant);
//				die();
				$contents = ob_get_contents();
				ob_end_clean();
				return $contents;
			}
			else 
			{
				return "[!instant unavailable!]";
			}
		}
		
		return '<'.'?php '.$result.'?'.'>';
	}
	
	/**
	 * Парсинг выражения с шаблонными переменными
	 * 
	 * ex.: (items.test && items.length) || !(noitems)
	 *
	 * @param string $expr
	 * @return string
	 */
	protected function parseExpression($expr)
	{
		// strip functions
		$expr = preg_replace_callback("/([a-zA-Z_0-9]+)\s*?\((.*?)\)/si", array(&$this, 'parseExpressionFunctionsCallback'), $expr);
		$expr = trim($expr);
		
		$result = '';
	
		$prevSymbol = '';	
		$inQuotes = false;
		$quoteSymbol = '';
		$word = '';
		
		for ($i = 0, $length = strlen($expr); $i < $length; $i++ )
		{
			$symbol = $expr{$i};
			
			// внутри кавычек
			if ($inQuotes)
			{
				if (($symbol == '"' && $prevSymbol != '\\' && $quoteSymbol == '"') || ($symbol == "'" && $prevSymbol != '\\' && $quoteSymbol == "'"))
				{
					$inQuotes = false;
				}
				$result .= $symbol;
			}
			// нужный нам символ
			elseif (preg_match('/[a-z0-9\.\#*_:\/]+/i', $symbol))
			{
				$word .= $symbol;
			}
			// какой то левый символ
			else
			{
				// начинаются кавычки
				if ($symbol == '"' || $symbol == "'")
				{
					$inQuotes = true;
					$quoteSymbol = $symbol;
				}
				// перед кавычкой было название функции
				elseif($symbol == '(')
				{
					$result .= $word;
					$word = '';
				}
				
				if (strlen($word) > 0)
				{
					// это цифры
					if (preg_match('/^[0-9\.]+$/i', $word))
					{
						$result .= $word;
					}
					else
					{
						if ($prevSymbol == '.')
						{
							$result .= $this->parseValue(substr($word, 0, strlen($word) - 2)).'.';
						}
						else
						{
							$result .= $this->parseValue($word);
						}
					}
					$word = '';
				}
				$result .= $symbol;
			}
			
			$prevSymbol = $symbol;
		}
		
		if (strlen($word) > 0)
		{
			// это цифры
			if (preg_match('/^[0-9\.]+$/i', $word))
			{
				$result .= $word;
			}
			else
			{
				$result .= $this->parseValue($word);
			}
		}
		
		return $result;		
//		return trim(preg_replace_callback('/(?<!["\'a-z0-9\._:\#*])([a-z0-9\.\#*_:]+)(?!["\'a-z0-9\(\._:\#*])/i', array(&$this, 'parseExpressionVarsCallback'), $expr));
	}
	
	protected function parseExpressionVarsCallback($matches)
	{
//		var_dump($matches);
		//		if (is_numeric($matches[2]))
//		{
//			return $matches[0];
//		}
//		else
//		{
			return $this->parseValue($matches[1]);
//		}
	}
	
	protected function parseExpressionFunctionsCallback($matches)
	{
		if ($matches[1] == 'count')
		{
			return $matches[1].'('.$matches[2].')';
		}
		else
		{
			return $matches[2];
		}
	}
	
	/**
	 * Парсинг шаблонной переменной 
	 * 
	 * ex.: items.test.value
	 *
	 * @param string $key
	 * @return string
	 */
	protected function parseValue($key)
	{
		$key = $this->applyShortcuts($key);
		
		if ($key{0} == '#')
		{
			$key = substr($key, 1);
		}
		
		$names = explode('.', $key);
		
		return $this->constructValue($names);
	}
	
	protected function constructValue($ks, $domain = '$tpl->domain')
	{
		$use_methods = false;
			
		foreach ($ks AS $i=>$k) 
		{
			if (strlen($k) == 0)
			{
				continue;
			}

			$func = '';
			$args = '';
			if (preg_match('#^[0-9]+$#', $k)) 
			{ // is numeric key ?
				$args = $k;
				$func .= 'a';
			} 
//			elseif($k == 'count')
//			{
//				
//			}
			else
			{
				$args = "'".$k."'";
				$func .= 'a';
			}
			
			
			
			// для первого ключа $this->domain всегда array()
//			if ($i !== 0) {
				// get object method or property
//				if (preg_match('#^[A-Za-z_][A-Za-z0-9_]*$#', $k)) {
					//$args[] = "'".$k."'";
//					$func .= 'p';
//					if ($use_methods) {
//						$method = ($this->tpl->tpl_construct_standard_getter_prefix
//							? $this->tpl->tpl_construct_standard_getter_prefix .'_'.$k // get_$k
//							: $k);
//						if ($this->tpl->tpl_construct_standard_camelCase) {
//							//$method = implode('', array_map('ucfirst', explode('_', $method))); // GetSomeValue
//							$method = implode('', explode('_', $method)); // getsomevalue
//						}
//						if (preg_match('#^[A-Za-z_][A-Za-z0-9_]*$#', $k)) {
//							$args[] = "'".$method."'";
//							$func .= 'm';
//						}
//					}
//				}
//			}

			$domain .= "[".$args."]";
		}

		return $domain;
	}
	
	protected function parseParam($thing)
	{
		// @template.html:name или @:name
		if ($thing{0} == '@')
		{
			$what = substr($thing, 1);
			if ($what{0} == ':') $what = $this->_template_name.'.html'.$what;
			$res = array(
			TE_TYPE => TE_TYPE_TEMPLATE,
			TE_VALUE => $what,
			);
		}
		else
		if (preg_match($this->tpl_string_regexp, $thing, $matches))
		{
			$what = $matches[2];
			$res = array(
			TE_TYPE => TE_TYPE_STRING,
			TE_VALUE => $what,
			);
		}
		else
		if (preg_match($this->tpl_arg_regexp, $thing, $matches))
		// [[var]]
		{
			$what = $matches[1];
			$res = array(
			TE_TYPE => TE_TYPE_VARIABLE,
			TE_VALUE => $what,
			);
		}
		else
		{
			$what = $thing;
			$res = array(
			TE_TYPE => TE_TYPE_STRING,
			TE_VALUE => $what,
			);

		}

		return $res;
	}

	protected function compileParam($param)
	{
		switch ($param[TE_TYPE])
		{
			case TE_TYPE_STRING: $res = $this->phpString($param[TE_VALUE]); break;
			case TE_TYPE_VARIABLE: $res = $this->parseExpression($param[TE_VALUE]); break;
			case TE_TYPE_TEMPLATE: $res = $this->phpString('@'.$param[TE_VALUE]); break;
			case TE_TYPE_PHP_SCRIPT: $res = $param[TE_VALUE]; break;
			default:
				throw new TplException("Compiler: undefined param type");
			break;
		}
		return $res;
	}

	protected function parseParams($content)
	{
		$params = array();
		// 2. link`em back
		// 3. get matches      1        2  		  34      5        6  7
		$c = preg_match_all( '/(^\s*|\s+)(?:([^=\s]+)=)?((["\']?)(.*?)\\4)($|(?=\s))/i',
		$content, $matches, PREG_SET_ORDER  );
		// 4. sort out
		$named = array();
		foreach( $matches as $match )
		{
			if ($match[2]) // named parameter
			{
				$params[ $match[2] ] = $this->parseParam($match[3]);
			}
			else // unnamed parameter
			{
				$params[] = $this->parseParam($match[3]);
			}
		}
		
//		foreach($named as $k=>$v) 
//		{
//			$params[$k] = $v;
//		}
		return $params;
	}

	protected function parseActionParams( $content )
	{
		$params = array();
//		$params["_plain"] = $this->parseParam('"'.$content.'"');
		
		// 1. get name by explosion
		$a = explode(" ", $content);
		$params["_name"] = $this->parseParam('"'.$a[0].'"'); // kuso@npj removed: strtolower($a[0]);
//		$params["_caller"] = $this->parseParam('"'.$this->_template_name.'"');
		if (count($a) == 1) return $params;

		$a = array_slice( $a, 1 );
		$_content = " ".implode(" ", $a);
		return array_merge($params, $this->parseParams($_content));
	}

	protected function phpString($what)
	{
		$res = '"'.
		str_replace( "\"", "\\\"",
		str_replace( "\n", "\\n",
		str_replace( "\r", "",
		str_replace( "\\", "\\\\", $what )))).'"';
		return $res;
	}

	protected function implodeActionParams( $params )
	{
		$result = 'array( ';
		$args = array();
		foreach( $params as $k=>$v )
		{
			$what = $this->compileParam($v);
			$args[] = '"'.strtolower($k).'" => '.$what;

		}
		$result.= implode(",\n", $args);
		$result.= ')';
		return $result;
	}

	/**
	 * Построение тела функции вокруг $content, включая { }
	 */
	protected function templateBuildFunction( $content )
	{
		$content = "{ ?>\n".$content."\n<"."?php }";
		return $content;
	}

	
	protected function writeToFile($fileCached, $functions, $filemode = 'w')
	{
		// можем ли записывать файл?
		$this->checkWritable($fileCached);
			
		// склеить готовый файл
		$fp = fopen( $fileCached , $filemode);
		fputs($fp, "<"."?php\n" );
		foreach($functions AS $fname=>$content)
		{
			fputs($fp, 'if(!defined(\''.$fname.'\')){define(\''.$fname.'\', true); function '.$fname.'(&$tpl)'."\n" );
			fputs($fp,$content);
			fputs($fp, "}\n");
		}
		fputs($fp, "?".">" );
		fclose($fp);
	}
	
	protected function writeActionsToFile($fileCached, $functions, $filemode = 'w')
	{
		// можем ли записывать файл?
		$this->checkWritable($fileCached);
			
		// склеить готовый файл
		$fp = fopen( $fileCached ,$filemode);
		fputs($fp, "<"."?php\n" );
		foreach($functions AS $fname=>$content)
		{
			fputs($fp, 'if(!defined(\''.$fname.'\')){define(\''.$fname.'\', true); function '.$fname.'(&$tpl, &$params)'."\n" );
			fputs($fp,$content);
			fputs($fp, "}\n");
		}
		fputs($fp, "?".">" );
		fclose($fp);
	}
	
	//zharik - проверяет можем ли мы писать в этот файл
	protected function checkWritable($file_to)
	{
		if (file_exists($file_to) && !is_writable($file_to) )
		{
			throw new TplException("Compiler: can't write to file *".$file_to."*");
		}

		if (!file_exists($file_to) && !is_writable($this->rh->cache_dir))
		{
			throw new TplException("Compiler: can't write to cache dir *".$this->rh->cache_dir."*");
		}
	}


	// EOC{ TemplateEngineCompiler }
}
?>