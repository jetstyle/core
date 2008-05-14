<?php
/*
  Компилятор шаблонного движка. Создаётся внутри TE, руками пользоваться не должен.

  TemplateEngineCompiler( &$tpl )

  ---------

  // Работа с шаблонами

  * TemplateCompile( $skin_name, $tpl_name, $file_from, $file_to ) -- возвращает true, если удачно
       - $skin_name -- в какую шкуру кэшировать
       - $tpl_name -- имя файла шаблона без расширения, например "news" для "news.html"
       - $file_from/$file_to -- полные имена файлов оригинала и кэша

  * _TemplateRead( $file_name ) -- возвращает array( name => content ), умолчательная передаётся
                                   через "@"=>...
  * _TemplateCompile( $content, $instant = false ) -- компилирует "контент" одного шаблона
       - $instant -- если в true, то вместо php-вставок сразу вставляет результат их работы
                     в текущей среде. Понятно, что условные конструкции здесь не должны быть 
                     использованы.
  * _ParseActionParams( $content )     -- парсит параметры action в массив
  * _ImplodeActionParams( $params )    -- превращает массив в php-строку, генерирующую этот массив
  * _TemplateBuildFunction( $content ) -- строит тело функции вокруг $content
  * _CheckWritable( $file_to ) -- проверяет права на запись в файл $file_to,
                    если прав нет, то проверяет права на запись в содержащую директорию.
                    трэйсит в debug
  * _ConstructGetValue($field_name) -- конструирует обращение к объекту $_ внутри компилята.
                    Обращение как к автомассиву, хэшу или объекту.

  // Работа с Actions

  * ActionCompile( $skin_name, $tpl_name, $file_from, $file_to ) -- возвращает true, если удачно
       - $skin_name -- в какую шкуру кэшировать
       - $action_name -- имя файла экшена без расширения, например "message" для "message.html"
       - $file_from/$file_to -- полные имена файлов оригинала и кэша


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

=============================================================== v.1 (kuso@npj, zharik@npj)
*/
define ('TE_TYPE', 0);
define ('TE_VALUE', 1);

define ('TE_TYPE_STRING', 0);
define ('TE_TYPE_TEMPLATE', 1);
define ('TE_TYPE_VARIABLE', 2);
define ('TE_TYPE_PHP_SCRIPT', 3);

class TemplateEngineCompiler
{
  var $rh;        // use: $this->rh->debug->Trace (..->Error)
  var $tpl;

  function TemplateEngineCompiler( &$tpl )
  {
    $this->tpl = &$tpl;
    $this->rh  = &$tpl->rh;
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

  // Работа с шаблонами

  function TemplateCompile( $skin_name, $tpl_name, $tpl_name_for_cache, $file_from, $file_to ) // -- возвращает true, если удачно
  {
    if (!file_exists($file_from))
      $this->rh->debug->Error( "TPL::TemplateCompile: file_from *".$file_from."* not found" );

    // 1. разобрать файл на кусочки
    if (!$pieces = $this->_TemplateRead( $tpl_name_for_cache, $file_from ))
      return false;

    // 2. скомпилировать кусочки
    $this->_template_name = $tpl_name;

    $functions = array();
    foreach( $pieces as $k=>$v )
    {
		 // объединяем одноимянные шаблоны в одну функцию if () elseif () ... else
		 // если кусок один -- тогда возвращаем без условий
		 // на выходе $fbody - тело функции
		 $fbody = '';
		 $single_pattern = False;
		 foreach ($v as $vv)
		 {
			 $args = $vv[0];
			 $body = $vv[1];
			 if (empty($fbody) && empty($args)) 
			 { 
				 // мы тут первый и последний раз. это {{:tpl}}
				 $fbody = $body; $single_pattern = True; break; 
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
		 if (!$single_pattern) $fbody .= '{{/?}}';
       $functions[ $this->rh->tpl_template_prefix.$skin_name.
                  $this->rh->tpl_template_sepfix.$tpl_name_for_cache.
                  $this->rh->tpl_template_sepfix.(($k=="@")?"":$k) ] = 
                  $this->_TemplateBuildFunction( $this->_TemplateCompile($fbody) );
    }

    // 3. можем ли записывать файл?
    $this->_CheckWritable($file_to);
    
    // 4. склеить готовый файл
    $fp = fopen( $file_to ,"w");
    fputs($fp, "<"."?php // made by Rockette. do not change manually, you mortal.\n\n" );
    foreach($functions as $fname=>$content)
    {
      fputs($fp, 'function '.$fname.'( &$tpl )'."\n" );
      fputs($fp,$content);
      fputs($fp, "\n\n" );
    }
    fputs($fp, "?".">" );
    fclose($fp);

    return true;
  }

  function _TemplateRead( /*ru:UNUSED*/$tpl_name, $file_name ) // -- возвращает array( name => content ), умолчательная передаётся через "@"=>...
  {
    // 0. get contents
    $contents = @file($file_name);
    if (!$contents){
      $this->rh->debug->Error("File $file_name not found, sorry");
      return false;
    }
    $contents = implode("",$contents); 

    // A. error_checking
    // -- .html templates
    $pi = pathinfo( $file_name );
    if ($pi["extension"] != "html") 
      $this->rh->debug->Error("TPL::_TemplateRead: [mooing duck alert] not .html template found @ $file_name!");
    // {{/TPL}}
    
    if (preg_match( "/".$this->rh->tpl_prefix."\/".
                        $this->rh->tpl_construct_tplt.
                        $this->rh->tpl_postfix."/si", $contents, $matches))
      $this->rh->debug->Error("TPL::_TemplateRead: [mooing duck alert] {{/TPL}} found!");


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

//    $this->rh->debug->Error( $contents );
    
    //zharik: protect ourselfs from '<?' in templates
    $contents = str_replace( "<?", "<<?php ; ?>?", $contents );

    // lucky: наследование шаблонов
    // {{#extends @path/to/base.html}}
    $is_extends = NULL;
    $re = $this->rh->tpl_prefix.'#extends'.'\s+@(.+?)\\.html\s*'.$this->rh->tpl_postfix;
    if (preg_match('/'. $re .'/i', $contents, $matches))
    {
      $is_extends = TRUE;
      $res = $this->_TemplateRead($tpl_name, $this->tpl->_FindTemplate($matches[1]));
      $contents = str_replace( $matches[0], '', $contents ); 
    }
    else
    {
      $is_extends = FALSE;
      $res = array();
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

  function _TemplateCompile( $content, $instant = false ) // -- компилирует "контент" одного шаблона
  {
    $this->_instant = $instant;
    $content = preg_replace_callback($this->long_regexp, array( &$this, "_TemplateCallback"), $content);
    return $content;
  }

  function _TemplateCallback( $things )
  {
    $_instant = false;

    $thing = $things[0];
    if ($thing{(strlen($this->rh->tpl_prefix))} == $this->rh->tpl_instant)
    {
      $thing = substr( $thing, 0, strlen($this->rh->tpl_prefix) ). 
               substr( $thing, strlen($this->rh->tpl_prefix)+1 );
      $_instant = true;
    }

    // {{!!action param=value param=value value}}....{{/!!}}
    if (preg_match($this->double_regexp, $thing, $matches))
    {             
      $params = $this->_ParseActionParams( $matches[1] );
      if (isset($params["instant"]) && $params["instant"][TE_VALUE]) $_instant = true; // {{!!typografica instant=1}}...{{/!!}} prerenders
//      $params["_"] = $matches[2];
      $param_contents = $this->_ImplodeActionParams( $params );
      $result = "ob_start();?>".$this->_TemplateCompile($matches[2])."<"."?php \n";
      $result .= ' $_='.$param_contents.';'."\n".' $_["_"] = ob_get_contents(); ob_end_clean();'."\n";
      $result .= ' echo $tpl->Action('.$this->_compileParam($params['_name']).', $_ ); ';
      $instant = $result;
    }
    else
    {
      // 1. rip {{..}}
      preg_match($this->single_regexp, $thing, $matches);
      #$thing = $matches[1];
      $thing = preg_replace('#[\\n\\r]#', '', $matches[1]);

      // 2. shortcuts
      foreach( $this->rh->shortcuts as $shortcut=>$replacement )
        if (strpos($thing, $shortcut) === 0)
        {
          if (!is_array($replacement)) $replacement = array($replacement, "");
          $thing = $replacement[0]. substr($thing, strlen($shortcut)). $replacement[1];
        }

      // {{?:}}
      if (strpos($thing, $this->rh->tpl_construct_ifelse) === 0)
      {
        if ($thing{2} == "!") $invert = "!"; else $invert="";
        $what = substr( $thing, strlen($invert)+2 );
		  if ($what)
		  {
			  $result =  ' } elseif ('.$invert.$this->_ConstructGetValueScript($what).') { ';
		  }
		  else
		  {
			  $result =  ' } else { ';
		  }
      }
      else
      // {{/?}}
      if ($thing == "/".$this->rh->tpl_construct_if)
      {
        $result = ' } ';
      }
      else
      // {{?var}}
      if ($thing{0} == $this->rh->tpl_construct_if)
      {
        if ($thing{1} == "!") $invert = "!"; else $invert="";
        $what = substr( $thing, strlen($invert)+1 );
        $result =  ' if ('.$invert.$this->_ConstructGetValueScript($what).') { ';
      } 
		else
		// {{!for *item do=template}}
		if (preg_match('#^!(for.*)$#', $thing, $matches))
		{
			$params = $this->_ParseActionParams( $matches[1] );
			
#            var_dump($matches[1]);
#            var_dump($params);
			$key = $params['each']?$params['each']:$params[0]; // ключ
			//можно без each= а сразу, {{!for news do=test.html:news}}	     
			$alias = $params['as']?$params['as']:NULL; // алиас
			//можно {{!for news as news_item do=test.html:news}}	     
			$template_name = $params['do']?$params['do']:$params['use']; // ключ

            /**  
            * @desc Примерно так делается в include, но тогда мы не сможем юзать класненький ассижн типа *varname=blabal,
            * а сможем только varname=blabla либо *=blabla (как в инклуде и есть)
            * 
            *            $__ = $this->_ImplodeActionParams($params);
            *            var_dump($__);
            *
            */
            $parsed = $this->_parseParam($thing);
            preg_match('#(.*)(do=)(.*)\s((.*)=(.*))#', $thing, $parsed);
#            var_dump($parsed);
            
            $paramka = $parsed[4];
            $assigned_key = $parsed[5];
#            var_dump($paramka);   

            
            $assignation = $this->_parseParams($paramka);
            //var_dump( $assignation );   
            
            $assigned_compiled =  $this->_compileParam($assignation[$assigned_key]);
            if (strpos($assigned_key,"*")===false)
            {
                $ass_key_noref = true;
            }
            else 
            {
                $assigned_key = str_replace("*","",$assigned_key);
            }            
#            var_dump($assigned_compiled);
#            var_dump($key);
#            die();
			$key = $this->_compileParam($key);
            

			if (isset($alias)) 
			{
				$item_store_to = $alias;
				$alias = $this->_compileParam($alias);
			}
			else $item_store_to = '"*"';

			$sep_tpl = $template_name;
			$item_tpl = $template_name;
			#$empty_tpl = $template_name;
			if((strpos($template_name, ":") === false))
			{
				$sep_tpl[TE_VALUE] .= "_sep";
				#$item_tpl[TE_VALUE] .= "_item";
				#$empty_tpl[TE_VALUE] .= "_empty";
			}
			else 
			{
				$sep_tpl[TE_VALUE] .= ":sep";
				#$item_tpl[TE_VALUE] .= ":item";
				#$empty_tpl[TE_VALUE] .= ":empty";
			}
			$sep_tpl = $this->_phpString($sep_tpl[TE_VALUE]);
			$item_tpl = $this->_phpString($item_tpl[TE_VALUE]);
			#$empty_tpl = $this->_phpString($empty_tpl[TE_VALUE]);


			$result = ' $_z = '.$key .";\n";
			$result .= '
if(is_array($_z) && !empty($_z))
{
    '.( $assigned_compiled && $ass_key_noref
          ?
           '$tpl->set("'.$assigned_key.'", '.$assigned_compiled.');'
          :
           ''
          )
        
    .'
   
    $sep = $tpl->parse('.$sep_tpl.');
	// надо чтобы его могло и не быть

	$old_ref =& $tpl->Get("*");
	$old__ =& $tpl->Get("_");
	$old_For =& $tpl->Get("For");

	$first = True;
	$num = 0;
	$for = array(
			"num"=>&$num,
		);
    $tpl->setRef("For", $for);
    $assigned_value = '.$assigned_compiled.';
	foreach($_z AS $r)
	{
		$num++;
		$for["odd"] = $num % 2;
		$for["even"] = !$for["odd"];
        '.( $assigned_compiled && !$ass_key_noref
          ?
           '$r["'.$assigned_key.'"]= $assigned_value;'
          :
           ''
          )
        
        .'
		$tpl->SetRef('.$item_store_to.', $r);

		if (True===$first) 
		{
			echo $tpl->parse('.$item_tpl.');
			$first = False;
		}
		else 
		{ 
			echo $sep;
			echo $tpl->parse('.$item_tpl.');
		}
		
	}

	$tpl->SetRef("*", $old_ref );
	$tpl->SetRef("_", $old__ );
	$tpl->SetRef("For", $old_For );
}
/*
else
{
	echo $tpl->parse('.$empty_tpl.');
}
 */

unset($_z);
				'."\n";
			$result .= ''."\n";
        $instant = $result;
		}
      else
      // {{!action param}}
      if (preg_match($this->action_regexp, $thing, $matches))
      {
          
        $params = $this->_ParseActionParams( $matches[1] );
#        var_dump($matches[1]);
#        var_dump($params);
#        die();
        // let`s make it instant!
		  if (in_array($params["_name"][TE_VALUE], $this->rh->tpl_instant_plugins) 
			  // lucky: hint in template
			  || (isset($params["instant"]) && $params["instant"][TE_VALUE]))
		  {
			  $_instant=true;
		  }

		  $result = '';
          $param_contents = $this->_ImplodeActionParams( $params );
#          var_dump($param_contents);
		  $result .= ' $_='.$param_contents.';';
		  $result .= ' $_r = $tpl->Action('
			  .$this->_compileParam($params['_name']).', $_ ); ';
			$result .= 'echo $_r; ';
        $instant = $result;
      }
      // {{#obj.property}}
      // {{var}}
      // {{var|plugin}}
      else
		{
			//if (preg_match($this->object_regexp, $thing, $matches)) { }
			//проверяем палка-синтаксис
			$A = array_map('trim', explode('|',$thing));
			$var = array_shift($A);
			$result = '';

			if (!empty($var))
			{
				$script = $this->_ConstructGetValueScript($var);
				$result .= ' $_r = '.$script .';'."\n";
			}

			foreach ($A as $stmt)
			{
				// FIXME:
				// c&p {{!action param}} 
				if (preg_match($this->action_regexp, '!'.$stmt, $matches))
				{
				  $params = $this->_ParseActionParams( $matches[1] );

				  $params['_'] = array(
					  TE_TYPE => TE_TYPE_PHP_SCRIPT,
					  TE_VALUE => '$_r'
				  );
				  $param_contents = $this->_ImplodeActionParams( $params );
				  $result .= ' $_='.$param_contents.';';
				  $result .= ' $_r = $tpl->Action('
					  .$this->_compileParam($params['_name']).', $_ ); ';
				}
				// с&p
			}
			/*
			if( count($A)>0 ){
				//есть палка-вхождения
				$result .= '$_formatters = array("'.implode('","',$A).'");'."\n";
				$result .= 'foreach($_formatters as $_f){'."\n";
				$result .= ' $_ = array("_plain"=>$_f,"_name"=>$_f,"_"=>$_r);'."\n";
				$result .= ' $_r = $tpl->Action($_f,$_);'."\n";
				$result .= "}\n";
			}
			 */
			$result .= 'echo $_r; ';
			$instant = $result;
		}
    }

    $tpl = &$this->tpl;
    if ($this->_instant || $_instant) 
      if ($instant) 
      { ob_start();
        eval($instant); 
        $contents = ob_get_contents();
        ob_end_clean();
        return $contents;
      }
      else return "[!instant unavailable!]";
    return '<'.'?php '.$result.'?'.'>';
  }


  function _ConstructGetValueScript($key)
  {
	  $data_sources = array(); // тут будем искать данные для each

	  $use_fixture = $this->rh->use_fixtures;

	  // lucky: FIXME: C&P from 
      foreach( $this->rh->shortcuts as $shortcut=>$replacement )
        if (strpos($key, $shortcut) === 0)
        {
          if (!is_array($replacement)) $replacement = array($replacement, "");
          $key = $replacement[0]. substr($key, strlen($shortcut)). $replacement[1];
        }

	  if ($key{0} == '#')
	  {  // шаблонная переменная #obj
		  $data_sources[] = '$tpl->domain';
		  $use_fixture = ($use_fixture && ($key{1} != '*')); // не используем фикстуры 
																	        // для '*foo'
		  $key = substr($key, 1);
	  }
	  else
	  { // данные из среды выполнения
		  // lucky: пока не понятно: 
		  //		как они туда попадут? 
		  //		и нафиг вообще нужны?
		  $data_sources[] = '$tpl->domain';
		  $data_sources[] = '$tpl->rh->tpl_data';
		  $use_fixture = ($use_fixture && True);
	  }
	  if ($use_fixture)
		{
		  // пошли за фикстурами
		  // lucky: тут $key уже без префиксов
		  $_key = array_shift(explode('.',$key));
		  $data_sources[] = '$tpl->rh->useFixture("fixtures", "'.$_key.'")';
		}

	  $value = $this->_ConstructGetValue($key);
	  $expr = $this->_ConstructGetValueScriptRe($data_sources, $value);
	  return $expr;

  }

  function _ConstructGetValueScriptRe($data_sources, $value)
  {
	  if (empty($data_sources)) return 'NULL';
	  $source = array_shift($data_sources);
	  $expr = '(((($_ = '.$source.') || True) && isset($_) && (($_t='.$value.')||True) && isset($_t)) ? $_t : ('.$this->_ConstructGetValueScriptRe($data_sources, $value).'))';
	  return $expr;
  }

  //нужно уметь обращаться к автомассиву
  function _ConstructGetValue( $f ){
	  $use_methods = True;
	 /* lucky: refactor
	  if( preg_match("/[\d]+/",$f) )
		return '$_['.$f.']';
	 else
		return 'is_array($_)?$_["'.$f.'"]:$_->'.$f.'';
	  */

	  /* lucky: если объект, можем вызвать геттер
		* можем сослаться на структуру внутри структуры struct.substruct.substruct
		* или так struct.getter().substruct
		*/
	  if (empty($f) && $f !== '0') return '$_';
	  else
	  {
		  $parts = explode($this->rh->tpl_construct_object{1},$f);
		  $k = array_shift($parts);
          
          /**
           * nop@jetstyle
           * хочу штуки типа {{?place_photos.count==1}}
           */
           
          if (preg_match("/(.*)(==|!=|<=|=>|=<|>=|>|<|\|\|)(.*)/i", $k, $matches)) //  
          {
            if (strpos($k, "||") )
          {
            	$matches[3] = $this->_ConstructGetValue( str_replace("*", "", $matches[3]) );
            }

            $k = $matches[1];
            $condition = $matches[2];
           // var_dump($condition);die();
            $value = $matches[3];
            if ($value{0}=="*")
            {
                
                #var_dump( $value );   
            
                $parsed_value = $this->_parseParams($value);
                #var_dump($parsed_value);
                //var_dump($parsed_value[TE_TYPE]);
                
                $compiled_value =  $this->_compileParam($parsed_value[0]);
                #var_dump($compiled_value);
                #die();
                $value=$compiled_value;
            }
          }
		  /* lucky:
			* если $_ массив: возвращаем значение по ключу $_[$k]
			* если $_ объект:
			*			если есть метод $_->$method() вернем результат вызова метода
			*			иначе возвращаем $_->$k
			*/
		  $method = (!empty($this->rh->tpl_construct_standard_getter_prefix)
			  ? $this->rh->tpl_construct_standard_getter_prefix .'_'.$k // get_$k
			  : $k );

		  if ($this->rh->tpl_construct_standard_camelCase)
			  $method = implode('', array_map('ucfirst', explode('_', $method))); // GetSomeValue

          /**
           * nop@jetstyle: я хочу такой хак
           * 1. {{array.count()}}    - в случае если ключ count занят в $_
           * 2. {{array.count}}      - короткий вариант в случае если нет ключа count
           */

          //жеский вариант, с проверкой в компиленном шаблоне
          //$check_for_count = ($k=="count" || $k=="count()") ? ' ("'.$k.'"=="count()" || "'.$k.'"=="count" && !isset($_["'.$k.'"]) ) ? count($_) :' : '';

          //помягче, без проверки, если это ничего не ломает
          $check_for_count = ($k=="count" || $k=="count()") ? ' true ? count($_) :' : '';

		  $res = '(is_array($_) ? '. ( $check_for_count ). ' $_["'.$k.'"] :'      
			  . ($use_methods && (preg_match ('#^[A-Za-z_][A-Za-z0-9_]*$#', $method)) 
			  ?  '(method_exists($_,"'.$method.'")?$_->'.$method.'():'
			  .(preg_match('#^[A-Za-z_]+$#', $k) ? '($_->'.$k.')' : 'NULL')
			  .'))'
			  : 'NULL)');
			  ;


          if ($condition)
          {
              $res = "(".$res.") ".$condition.$value; 
              //echo '<hr>';
              //var_dump($res);
          }
          
		  return (empty($parts)
			  ? $res 
			  /* lucky: я обожаю этот язык Ж), но тут требуют lvalue 
				* ((($result = new_value()) || True) ? $result : 'никогда не вернем');
				*/
			  : '((($_='.$res.')||True)?'
												  .$this->_ConstructGetValue(
													  implode($this->rh->tpl_construct_object{1}, $parts))
									     .':NULL)');
	  }
  }

  function _parseParam($thing)
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

  function _compileParam($param)
  {
	  switch ($param[TE_TYPE])
	  {
	  case TE_TYPE_STRING: $res = $this->_phpString($param[TE_VALUE]); break;
	  case TE_TYPE_VARIABLE: $res = $this->_ConstructGetValueScript($param[TE_VALUE]); break;
	  case TE_TYPE_TEMPLATE: $res = $this->_phpString('@'.$param[TE_VALUE]); break;
	  case TE_TYPE_PHP_SCRIPT: $res = $param[TE_VALUE]; break;
	  default: $this->rh->error('Unknown type'); break;
	  }
	  return $res;
  }

  function _parseParams($content)
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
        $named[ $match[2] ] = $this->_parseParam($match[3]);
      else // unnamed parameter
        $params[] = $this->_parseParam($match[3]);
    }
    foreach($named as $k=>$v) $params[$k] = $v;
    return $params;
  }

  function _ParseActionParams( $content )
  {
    $params = array();
    $params["_plain"] = $this->_parseParam('"'.$content.'"');
    // 1. get name by explosion
    $a = explode(" ", $content);
    $params["_name"] = $this->_parseParam('"'.$a[0].'"'); // kuso@npj removed: strtolower($a[0]);
    $params["_caller"] = $this->_parseParam('"'.$this->_template_name.'"');
    if (count($a) == 1) return $params;

    $a = array_slice( $a, 1 );
    $_content = " ".implode(" ", $a);
	 return array_merge($params, $this->_parseParams($_content));
  }

  function _phpString($what)
  {
	  $res = '"'.
					 str_replace( "\"", "\\\"",
					 str_replace( "\n", "\\n",
					 str_replace( "\r", "",
					 str_replace( "\\", "\\\\", $what )))).'"';
	  return $res;
  }

  function _ImplodeActionParams( $params )
  {
	  $result = 'array( ';
	  $args = array();
	  foreach( $params as $k=>$v )
	  {
		  $what = $this->_compileParam($v);
		  $args[] = '"'.strtolower($k).'" => '.$what;

	  }
	 $result.= implode(",\n", $args);
    $result.= ')';
    return $result;
  }

  /** 
	* строит тело функции вокруг $content, включая { }
	*/
  function _TemplateBuildFunction( $content ) 
  {
		$content = "{ ?>\n".$content."\n<"."?php }";
		return $content;
  }

  //zharik
  /*
    Код перенесён и адаптирован с TemplateCompile.
    Я старался, что бы эти две функции выглядели похоже, поэтому оставил массив $functions и 
    пробег по нему при склейке файла. Имхо, потерь производительности при этом почти никаких.
  */
  function ActionCompile( $skin_name, $action_name, $action_name_for_cache, $file_from, $file_to ) // -- возвращает true, если удачно
  {
    if (!file_exists($file_from))
      $this->rh->debug->Error( "TPL::ActionCompile: file_from *".$file_name."* not found" );
    
    //1. обернуть экшен как функицю
    //построение стандартного окружение
    $enviroment = "\n<"."?php \$rh =& \$tpl->rh; ?>\n";
    $enviroment .= implode( '', file( $this->rh->FindScript_('handlers','_enviroment') ) )."\n";
    //собственно оборачивание
    $functions = array();
    $functions[ $this->rh->tpl_action_prefix.$skin_name.
                $this->rh->tpl_template_sepfix.$action_name_for_cache ] = 
                $this->_TemplateBuildFunction( $enviroment.
                  preg_replace("/\/\*.*?\*\//s","",implode('',@file($file_from)))
                );
    
    // 2. можем ли записывать файл?
    $this->_CheckWritable($file_to);
    
    // 3. склеить готовый файл
    $fp = fopen( $file_to ,"w");
    fputs($fp, "<"."?php // made by Rockette. do not change manually, you mortal.\n\n" );
    foreach($functions as $name=>$content)
    {
      fputs($fp, 'function '.$name.'( &$tpl, &$params )'."\n" );
      fputs($fp,$content);
      fputs($fp, "\n\n" );
    }
    fputs($fp, "?".">" );
    fclose($fp);

    return true;
  }

  //zharik - проверяет можем ли мы писать в этот файл
  function _CheckWritable($file_to)
  {
    if (file_exists($file_to) && !is_writable($file_to) )
     $this->rh->debug->Error( "TPL::_CheckWritable: No access to -> ". $file_to );
    
    if (!file_exists($file_to) && !is_writable($this->rh->cache_dir))
     $this->rh->debug->Error( "TPL::_CheckWritable: No access to entire cache dir -> ". $this->rh->cache_dir );
  }


// EOC{ TemplateEngineCompiler } 
}


?>
