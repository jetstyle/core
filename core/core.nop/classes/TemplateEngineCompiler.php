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
  * _ConctructGetValue($field_name) -- конструирует обращение к объекту $_ внутри компилята.
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

  $rh->tpl_instant_plugins = array( "dummy" );

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
         "/^".$this->rh->tpl_prefix."(.+?)".$this->rh->tpl_postfix."$/i";
    $this->double_regexp = 
         "/^".$this->rh->tpl_prefix.$this->rh->tpl_construct_action2.
              "(.+?)".$this->rh->tpl_postfix.
              "(.*?)".
              $this->rh->tpl_prefix."\/".$this->rh->tpl_construct_action2.
              "(\\1)?".$this->rh->tpl_postfix."$/si";
    // single regexps
    $this->object_regexp = 
         "/^".$this->rh->tpl_construct_object{0}."(.+)".
          "[".$this->rh->tpl_construct_object{1}."](.+)$/i";
    $this->action_regexp = 
         "/^".$this->rh->tpl_construct_action."(.+)$/i";

  }

  // Работа с шаблонами

  function TemplateCompile( $skin_name, $tpl_name, $file_from, $file_to ) // -- возвращает true, если удачно
  {
    if (!file_exists($file_from))
      $this->rh->debug->Error( "TPL::TemplateCompile: file_from *".$file_from."* not found" );

    // 1. разобрать файл на кусочки
    if (!$pieces = $this->_TemplateRead( $tpl_name, $file_from ))
      return false;

    // 2. скомпилировать кусочки
    $this->_template_name = $tpl_name;

    $functions = array();
    foreach( $pieces as $k=>$v )
    {
      $functions[ $this->rh->tpl_template_prefix.$skin_name.
                  $this->rh->tpl_template_sepfix.$tpl_name.
                  $this->rh->tpl_template_sepfix.(($k=="@")?"":$k) ] = 
                  $this->_TemplateBuildFunction( $this->_TemplateCompile($v) );
    }

    // 3. можем ли записывать файл?
    $this->_CheckWritable($file_to);
    
    // 4. склеить готовый файл
    $fp = fopen( $file_to ,"w");
    fputs($fp, "<"."?php // made by Rockette. do not change manually, you mortal.\n\n" );
    foreach($functions as $name=>$content)
    {
      fputs($fp, 'function '.$name.'( &$tpl )'."\n" );
      fputs($fp,$content);
      fputs($fp, "\n\n" );
    }
    fputs($fp, "?".">" );
    fclose($fp);

    return true;
  }

  function _TemplateRead( $tpl_name, $file_name ) // -- возвращает array( name => content ), умолчательная передаётся через "@"=>...
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
                              
    // 2. grep all {{TPL:..}} & replace `em by !includes
    $include_prefix = $this->rh->tpl_prefix.$this->rh->tpl_construct_action."include ";
    $stack     = array( $contents );
    $stackname = array( "@" );
    $stackpos = 0;

    //разрезаем на подшаблоны
    while ($stackpos < sizeof($stack) )
    { 
      $data = $stack[$stackpos];
      $c =preg_match_all( "/".$this->rh->tpl_prefix.
                          $this->rh->tpl_construct_tplt."([A-Za-z0-9_]+)".
                          $this->rh->tpl_postfix."(.*?)".
                          $this->rh->tpl_prefix."\/".
                          $this->rh->tpl_construct_tplt."\\1".
                          $this->rh->tpl_postfix."/si",                     
                          $data, $matches, PREG_SET_ORDER  );
      foreach( $matches as $match )
      {
        $sub = $tpl_name.".html:".$match[1];
        //$data = str_replace( $match[0], $include_prefix.$sub.$this->rh->tpl_postfix, $data );
        $data = str_replace( $match[0], '', $data ); // ru@jetstyle

        $stack[] = $match[2];
        $stackname[] = $match[1];
      }

      /* ru@jetstyle скопипастил чтобы шаблоны можно было метить облегчённо */
      $c =preg_match_all( "/".$this->rh->tpl_prefix.
                          $this->rh->tpl_construct_tplt2."([A-Za-z0-9_]+)".
                          $this->rh->tpl_postfix."(.*?)".
                          $this->rh->tpl_prefix."\/".
                          $this->rh->tpl_construct_tplt2."\\1".
                          $this->rh->tpl_postfix."/si",                     
                          $data, $matches, PREG_SET_ORDER  );
      foreach( $matches as $match )
      {
        $sub = $tpl_name.".html:".$match[1];
        $data = str_replace( $match[0], '', $data ); 
        $stack[] = $match[2];
        $stackname[] = $match[1];
      }
      /* ru@jetstyle скопипастил досюда */

      $stack[$stackpos] = $data;
      $stackpos++;
    }

    // pack results
    $res = array();
    foreach( $stack as $k=>$v )
    {
         $res[ $stackname[$k] ] = $v;
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
      if (isset($params["instant"]) && $params["instant"]) $_instant = true; // {{!!typografica instant=1}}...{{/!!}} prerenders
//      $params["_"] = $matches[2];
      $param_contents = $this->_ImplodeActionParams( $params );
      $result = "ob_start();?>".$this->_TemplateCompile($matches[2])."<"."?php \n";
      $result .= ' $_='.$param_contents.';'."\n".' $_["_"] = ob_get_contents(); ob_end_clean();'."\n";
      $result .= ' echo $tpl->Action("'.$params["_name"].'", $_ ); ';
      $instant = $result;
    }
    else
    {
      // 1. rip {{..}}
      preg_match($this->single_regexp, $thing, $matches);
      $thing = $matches[1];

      // 2. shortcuts
      foreach( $this->rh->shortcuts as $shortcut=>$replacement )
        if (strpos($thing, $shortcut) === 0)
        {
          if (!is_array($replacement)) $replacement = array($replacement, "");
          $thing = $replacement[0]. substr($thing, strlen($shortcut)). $replacement[1];
        }

      // {{?:}}
      if ($thing == $this->rh->tpl_construct_ifelse)
      {
        $result =  ' } else { ';
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
        //в условный оператор можно писать свойства объектов
        if (preg_match($this->object_regexp, str_replace('*','#*.',$what), $matches))
        {
          $result = ' $_ = $tpl->Get( "'.$matches[1].'" ); '.
                    ' if('.$invert."(".$this->_ConstructGetValue($matches[2]).')) { ';
        }
        else $result =  ' if ('.$invert.'$tpl->Get("'.$what.'")) { ';
      } 
      else
      // {{!action param}}
      if (preg_match($this->action_regexp, $thing, $matches))
      {
        $params = $this->_ParseActionParams( $matches[1] );

        // let`s make it instant!
        if (in_array($params["_name"], $this->rh->tpl_instant_plugins)) $_instant=true;

        $param_contents = $this->_ImplodeActionParams( $params );
        $result =  ' $_='.$param_contents.'; echo $tpl->Action("'.$params["_name"].'", $_ ); ';
        $instant = $result;
      }
      else
      // {{#obj.property}}
      if (preg_match($this->object_regexp, $thing, $matches))
      {
        $result = ' $_ = $tpl->Get( "'.$matches[1].'" ); '.
                  ' echo '.$this->_ConstructGetValue($matches[2]).'; ';
        $instant = $result;
      }
      else
      // {{var}}
      {
        //проверяем палка-синтаксис
        $A = explode('|',$thing);
        if( count($A)>1 ){
          //есть палка-вхождения
          $result = ' $_r = $tpl->Get("'.$A[0].'");'."\n";
          $result .= '$_formatters = array("'.implode('","',array_slice($A,1)).'");'."\n";
          $result .= 'foreach($_formatters as $_f){'."\n";
          $result .= ' $_ = array("_plain"=>$_f,"_name"=>$_f,"_"=>$_r);'."\n";
          $result .= ' $_r = $tpl->Action($_f,$_);'."\n";
          $result .= "}\n";
          $result .= 'echo $_r; ';
        }else
          //нет палка-вхождений
          $result = ' echo $tpl->Get( "'.$thing.'" ); ';
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

  //нужно уметь обращаться к автомассиву
  function _ConstructGetValue( $f ){
    if( preg_match("/[\d]+/",$f) )
      return '$_['.$f.']';
    else
      return 'is_array($_)?$_["'.$f.'"]:$_->'.$f.'';
  }

  function _ParseActionParams( $content )
  {
    $params = array();
    $params["_plain"] = $content;
    // 1. get name by explosion
    $a = explode(" ", $content);
    $params["_name"] = $a[0]; // kuso@npj removed: strtolower($a[0]);
    if (sizeof($a) == 1) return $params;
    $params["_caller"] = $this->_template_name;

    // 2. link`em back
    $a = array_slice( $a, 1 );
    $_content = " ".implode(" ", $a);
    // 3. get matches      1     2       3 45       6    7      8  9
    $c = preg_match_all( "/(^|\s)([^= ]+)(=((\"|')?)(.*?)(\\4))?($|(?=\s))/i",
                         $_content, $matches, PREG_SET_ORDER  );
    // 4. sort out
    $named = array();
    foreach( $matches as $match )
    {
      if ($match[3]) // named parameter
        $named[ $match[2] ] = $match[6];
      else // unnamed parameter
        $params[] = $match[2];
    }
    foreach($named as $k=>$v) $params[$k] = $v;
    return $params;
  }

  function _ImplodeActionParams( $params )
  {
    $result = 'array( ';
    $f=0;
    foreach( $params as $k=>$v )
    {
      if ($f) $result.=",\n"; else $f=1;
      $result.= '"'.strtolower($k).'" => "'.
          str_replace( "\"", "\\\"",
          str_replace( "\n", "\\n",
          str_replace( "\r", "",
          str_replace( "\\", "\\\\", $v )))).'"';
    }
    $result.= ')';
    return $result;
  }

  function _TemplateBuildFunction( $content ) // -- строит тело функции вокруг $content, включая { }
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
  function ActionCompile( $skin_name, $action_name, $file_from, $file_to ) // -- возвращает true, если удачно
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
                $this->rh->tpl_template_sepfix.$action_name ] = 
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