<?php
/*
  ��������� ������
  * ������������� �������� � PHP
  * php actions, conditions, includes

  TemplateEngine( &$rh )

  ---------

  // ������ � ������� ����������

  * Get( $key )            -- �������� �������� (��������� ��� ������)
  * Set( $key, $value=1 )  -- ���������� �������� �����
  * SetRef( $key, &$ref )  -- ���������� �������� �������
  * Append( $key, $value ) -- �������� � ����� 
  * Is( $key )             -- true, ���� ���� ���� ���� ���-�� ������ (isset is a keyword)
  * Free( $key="" )        -- ������� ������ ��� unset ����������
      - $key   -- ��� ���������� (����), ���� ������, �� ��������� ���� �����
  * Load( $domain )        -- �������� ������� � �����

  // ������� ��������

  * Parse( $tpl_name, $store_to="", $append=0, $dummy="" ) -- ������� ������� � ����������� ����������. 
                                                              ���������� ������-���������
      - $tpl_name    -- ��� ��������, �������� "front.html:Menu" ��� "front.html"
      - $store_to    -- ���� �����������, �� ��������� ����� ����������� � ���������� ������ � ����� ������
      - $append      -- ���� �������� $store_to, �� ��������� �� ������� �������� ����������, � ������������ � �����
      - $dummy       -- �� ��� �������� �� ����������, ������� � ������ �� ��������

  * ParseInstant( $template_content ) -- "���������" �������, ������ ��� �������� (� �� ����� �� �����)

  * _SpawnCompiler() -- ��������� �������������� �����

  * _FindTemplate( $tpl_filename, $level=false, $direction=-1 ) -- ���������� ������� � ������ ���� � �����,
                                                                   � ������� ����� ��� ����� (���)�������
   
  // �����

  * Skin( $skin_name="" )   -- ����������� ������� � ������ ����� (�������� ������� �����)
  * UnSkin()                -- ��������� � ���������� �����
  * _SetSkin( $skin_name )  -- ��� ����������� �����������, ������������� ������� ����������

  // �������� ���������� ���������� ������

  - Action( $action_name, &$params, $level=false, $direction=-1 ) -- ����� ��������
      - $action_name -- lowered case �������� ��������
      - $level       -- � ������ ������ ������ "actions" ������.
                        ���� �� �������, �� ������� ������ � ����� ������ �����, ����� 
                        ���� �� ����� ����, ����� �� ������ ��� -- "����"
      - $direction   -- ����������� ������, �� ���������: �� ���� � ����

  // ����������������� actions, ������� ����� ����� ����� ����� ����:
  * action_ActionName -- "���������" �����, ������ ����� � ������ ��� ��������

  * _Message( $tag )
  * _Text   ( $tag )

  // �������� (ForR2, ???)
  ? _Connect( $what )             
  ? _Inline ( $what, $as = "js" ) 
  ? _Link   ( $href, $text="", $title = "")

  // ������ �� �������� (�������)

  * Loop( &$ITEMS, $tpl_root, $store_to='', $append=false, $implode=false ) -- ������� ������ �� ��������� ��������
       - $ITEMS             -- ������, ������� ���� ���������� � ������ (����� *-��������)
       - $tpl_root          -- �������, � �������� ���������� ���������� ������.
       - $store_to, $append -- ���������� TE::Parse
       - $implode           -- "���������" �� ��������� ����� ��������� "�����������"

    ������ ��������� �������� ������ � ����� "test.html" (� �������� $tpl_root="test.html:List"):
      {{TPL:List}}  ... {{TPL:List_Item}}one item{{/TPL:List_Item}} ... {{/TPL:LIST}}
      {{TPL:List_Empty}} ���� ������ ���� {{/TPL:List_Empty}}
      {{TPL:List_Separator}} | {{/TPL:List_Separator}}
    ��. ����� ����� ListObject

  // ���. ���������

  * $this->msg -- ����� ���� ������ ������ MessageSet


  ---------

  ����������� ��������� � $rh:

  $rh->tpl_markup_level  = TPL_MODE_CLEAN;
  $rh->tpl_compile       = TPL_COMPILE_SMART;
  $rh->tpl_root_dir      = "themes/";  // or "../" or "" -- ��� ����� �����
  $rh->tpl_root_href     = "/themes/"; // or "/"         -- ��� ��������� �� URL �� ���
  $rh->tpl_skin          = ""; // for no-skin-mode
  $rh->tpl_skin_dirs     = array( "css", "js", "images" ); // -- ����� �������� �������

  $rh->tpl_action_prefix      = "rockette_action_";
  $rh->tpl_template_prefix    = "rockette_template_";
  $rh->tpl_template_sepfix    = "__";
  $rh->tpl_action_file_prefix   = "@@"; 
  $rh->tpl_template_file_prefix = "@";

  $rh->cache_dir              = "../_zcache/"; // or "_zcache/" -- ���� ������ ���

  ---------

  NB: ��� ������� ������ ���� .html -- ��� �������� �������


=============================================================== v.2 (kuso@npj, zharik@npj)
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
  var $rh;        // use: $this->rh->debug->Trace (..->Error)
  var $msg = false;
  var $domain;

  var $CONNECT = array();

  function TemplateEngine( &$rh )
  {
    $this->domain = array();
    $this->rh     = &$rh;

    // ����������� ���� ���� �� ������ ����� RH
    $this->DIRS = $rh->DIRS;

    // ������� �����
    $this->Skin( $rh->tpl_skin );

    // ��������� ������������
    $this->skin_names = array(); // ����� ����
  }

  // ������ � ������� ���������� -------------------------------------------------------------

  function Get( $key ) // -- �������� �������� (��������� ��� ������)
  { return isset($this->domain[$key]) ? $this->domain[$key] : "" ; }

  function Set( $key, $value=1 )  // -- ���������� �������� �����
  { $this->domain[$key] = $value; }

  function SetRef( $key, &$ref )  // -- ���������� �������� �������
  { $this->domain[$key] = &$ref; }

  function Append( $key, $value ) // -- �������� � ����� 
  { $this->domain[$key] .= $value; }

  function Is( $key ) // -- true, ���� ���� ���� ���� ���-�� ������
  { return isset( $this->domain[$key] ); }

  function Free( $key="" ) // -- ������� ������ ��� unset ����������
  { if ($key === "") $this->domain = array();
    else if( is_array($key) )
    {
      foreach($key as $k)
      unset( $this->domain[$k] );
    } else unset( $this->domain[$key] );
  }

  function Load( $domain ) // -- �������� ������� � �����
  {
    foreach($domain as $k=>$v)
    {
      $this->Set( $k, $v );
    }
  }

  // ����� ------------------------------------------------------------------------------------

  function Skin( $skin_name="" ) // -- ����������� ������� � ������ ����� (�������� ������� �����)
  {
    // ��������� ������� ��� FindScript
    $dir = $this->rh->tpl_root_dir.$skin_name;
    if ($skin_name != "") $dir.="/";
    $this->DIRS[] = $dir;
    // ��������� ��� �����
    $this->skin_names[] = $skin_name;
    // ���������� �����
    return $this->_SetSkin( $skin_name );
  }

  function UnSkin() // -- ��������� � ���������� �����
  {
    array_pop( $this->DIRS );
    array_pop( $this->skin_names );
    return $this->_SetSkin( $this->skin_names[ sizeof($this->DIRS)-1 ] );
  }

  function _SetSkin( $skin_name ) // -- ��� ����������� �����������
  {
    $this->Set( "skin", $this->rh->tpl_root_href.$skin_name );
    foreach($this->rh->tpl_skin_dirs as $k=>$dir)
      $this->Set( $dir, $this->rh->tpl_root_href.$skin_name."/".$dir."/" );
    $this->_skin = $skin_name;
  }

  // ������� �������� --------------------------------------------------------------------------

  function _SpawnCompiler() // -- ��������� �������������� �����
  {
    if (!isset($this->compiler))
    {
      require_once( dirname(__FILE__)."/TemplateEngineCompiler.php" );
      $this->compiler =& new TemplateEngineCompiler( $this );
    }
  }

  function _FindTemplate( $tpl_filename ) // -- ���������� ������� � ������ ���� � ���-�����
  {
    // 2. launch parent
    return ConfigProcessor::FindScript_( "templates", $tpl_filename, false, -1, "html" );
  }

  function ParseInstant( $template_content ) // -- "���������" �������, ������ ��� �������� (� �� ����� �� �����)
  {
    // 1. ��� ����������� ����������!
    $this->_SpawnCompiler();
    // 2. ��������������� �������
    return $this->compiler->_TemplateCompile( $template_content, true ); // instant=true
  }

  function Parse( $tpl_name, $store_to="", $append=0, $dummy="" ) // -- ������� ������� � ����������� ����������. 
  { 
  	  
    // 1. split tplname by :
    $a = explode( ":", $tpl_name );
    $name0 = $a[0]; // ��� �����
    if (sizeof($a) > 1) $_name = $a[1]; // ��� ����������
    else                $_name = "";
    // ��� �� ����������, �������� ��������� ��� ������� ��� ����������
    $_pos = strrpos($tpl_name, ".");
    $name = $_pos ? substr($name0, 0, $_pos) : $name0; 
    // ��� ������� ��� �����������
    $tname=preg_replace("/[^\w\x7F-\xFF\s]/", $this->rh->tpl_template_sepfix, $name);

    Debug::trace("Parsing: ".$tpl_name, 'tpl');

$func_name = $this->rh->tpl_template_prefix.$this->_skin.
                    $this->rh->tpl_template_sepfix.$tname.
                 $this->rh->tpl_template_sepfix.$_name;

	

    // ????? kuso@npj: ����� ������� ���������, ��� �� � ��� ��� ����� �������.
    //       ���� �� �������� ����-���� ��� �����

    // 2. ��������� ��� ������ ��������� � ���������
    $file_cached = $this->rh->cache_dir.
                   $this->rh->environment.$this->rh->tpl_template_sepfix.
                   $this->_skin.$this->rh->tpl_template_file_prefix.
                   //���� �� ��������, ����� ������ �������������� tpl_template_file_prefix ������ tpl_cache_prefix
                   //$this->rh->tpl_cache_prefix.
                   $tname.".php";
    
    
    Debug::trace("Should be cached as: ".$file_cached, 'tpl');

    // 3. �������� ������� � ����/������������� ������������
    $recompile = $this->rh->tpl_compile != TPL_COMPILE_NEVER;
    $recompile = $recompile || !file_exists( $file_cached );
    if ($recompile)
    {
      // hack 
      // $name can be full path to template
      if(file_exists($name.'.html'))
      {
      	$file_source = $name.'.html';
      }
      else
      {
      $file_source = $this->_FindTemplate( $name );
      }
                                                       
      Debug::trace( "source:".$file_source ."($name)", 'tpl' );
      Debug::trace( "cache to:".$file_cached. "($tname)", 'tpl' );

      if ($file_source && ($this->rh->tpl_compile != TPL_COMPILE_ALWAYS))
        if (@filemtime($file_cached) >= @filemtime($file_source)) $recompile = false;
    }
    // 4. ��������������
    if ($recompile) 
    { 
      $this->_SpawnCompiler();
      $this->compiler->TemplateCompile( $this->_skin, $name, $tname, $file_source, $file_cached );
    }
    // 5. �������-����
    
    include_once( $file_cached );

    $func_name = $this->rh->tpl_template_prefix.$this->_skin.
                    $this->rh->tpl_template_sepfix.$tname.
                 $this->rh->tpl_template_sepfix.$_name;

    if (function_exists ($func_name)) { // ru@jetstyle
      ob_start();
      $func_name($this);
      $res = trim(ob_get_contents());
      ob_end_clean();
    } else {
      //$this->rh->debug->Error( "Subtemplate ".$tpl_name." is not exists" );      
      //return false;
	  try
	  {
        throw new TplException("Func_name=<b>" . $func_name . "</b>, Sub_tpl_name=<b>" . $_name . "</b>, Sub_tpl_source=<b>" . $file_source . "</b>, Tpl=<b>" . implode("\n", file($file_source)) . "</b>");
      }
      catch (TplException $e)
      {
        $exceptionHandler = ExceptionHandler::getInstance();
        $exceptionHandler->process($e);
      }
    }
    
    //6. $dummy
    if( $res=='' ) $res = $dummy;
    
    $res = preg_replace("/<sup.\/>/", "", $res);

    
    //7. $store_to & $append
    if( $store_to )
      if( $append )
        $this->domain[ $store_to ] .= $res;
      else
        $this->domain[ $store_to ] = $res;
    
    return $res;
  }

  // �������� ���������� ���������� ������ (Actions) -----------------------------------------------

  //zharik
  function Action( $action_name, &$params, $level=0, $direction=1 ) // -- ����� ��������
  {
    // by kuso@npj, 16-09-2004
    $action_name_for_cache = str_replace("/", "__", $action_name);

    //��������� - � �� ��������� �� ��� �����?
    $func_name = 'action_'.$action_name_for_cache;
    
    if( method_exists( $this, $func_name) )
      return $this->$func_name( $params );

    //���������� ��� �������
    $func_name = $this->rh->tpl_action_prefix.$this->_skin.
                 $this->rh->tpl_template_sepfix.$action_name_for_cache;
    
    //��������� ����� �� �������������
    if( !function_exists($func_name) )
    {
      //��������� ��� ������ ��� ��������� � ���������
      $file_cached = $this->rh->cache_dir.
                     $this->rh->tpl_action_file_prefix.
                     $this->rh->environment.$this->rh->tpl_template_sepfix.
                     $this->_skin.$this->rh->tpl_template_sepfix.
                     $action_name_for_cache.".php";
      
      //�������� �� ������������� ����������
      $recompile = $this->rh->tpl_compile != TPL_COMPILE_NEVER;
      $recompile = $recompile || !file_exists( $file_cached );
      if ($recompile)
      {
	    try
	    {
          if (!$file_source = $this->FindScript( "plugins", $action_name, $level, $direction ))
            throw new TplException("Func_name=<b>" . $func_name . "</b>, Action_name=<b>" . $action_name . "</b>, Tpl_name=<b>" . $params["_caller"] . "</b>, Tpl_source=<b>" . $this->_FindTemplate( $params["_caller"] ) . "</b>, Tpl=<b>" . implode("\n", file($this->_FindTemplate($params["_caller"]))) . "</b>", 1);
        }
        catch (TplException $e)
        {
          $exceptionHandler = ExceptionHandler::getInstance();
          $exceptionHandler->process($e);
        }
        Debug::trace( $file_source, 'tpl' );
        Debug::trace( $file_cached, 'tpl' );
        
        if ($file_source && ($this->rh->tpl_compile != TPL_COMPILE_ALWAYS))
          if (@filemtime($file_cached) >= @filemtime($file_source)) $recompile = false;
      }
      
      //��������������� �������
      if ($recompile) 
      {
        $this->_SpawnCompiler();
        $this->compiler->ActionCompile( $this->_skin, $action_name, $action_name_for_cache, $file_source, $file_cached );
      }
      
      //���������� �������
      include_once( $file_cached );
    }
    
    //��������� � ���������� ���������
    ob_start();
    echo $func_name( $this, $params );
    $_ = trim(ob_get_contents());
    ob_end_clean();
    return $_;
  }
  
  // Aliases

  function _Message( $tag )
  {
    if ($this->msg) return $this->msg->Get( $tag );
    else return $tag;
  }
  function _Text( $tag )
  {
    if ($this->rh->db) 
    {
    	throw new Exception("Rockette::_Text -> Db not implemented");
    }
    else 
    {
    	return $this->_Message( $tag );
    }
  }


  // ��������� �������

  function action_include( &$params )
  {
	  //return $this->parse(substr( $params[0], 1 ));

	  // lucky: from !render
	  $template = substr( $params[0], 1 );
	  // ����� ������� caller, ���� ���������� ��������� ������
	  unset($params[0]);
	  unset($params['_name']);
	  unset($params['_plain']);
	  unset($params['_caller']);

	  $stack = array(); // lucky: context saving storage

	  foreach( $params as $key => $v)
	  {
		  if ($v[0]=='@')
		  {
			  $subtemplate = substr( $v, 1 );

			  // ����� �������� ����������� �������� ��� �������� �������
			  // � ������� caller, ���� ���������� ��������� ������
			  $stack[$key] =& $this->get($key);
			  $this->Set($key,$this->parse($subtemplate));
		  }
		  // lucky: HACK set objects.. check param types in future
		  elseif (is_array($v) || is_object($v))
		  {
			  $stack[$key] =& $this->get($key);
			  $this->SetRef($key, $params[$key] );
		  }
		  else
		  {
			  // ���� � ��� � ���������� ������������ ���������� �����������,
			  // �������� [[images]]
			  $v = str_replace('[[','{{',$v);
			  $v = str_replace(']]','}}',$v);
			  $stack[$key] =& $this->get($key);
			  $this->Set($key,$this->ParseInstant( $v ));
		  }
	  }

	  //echo( $template );

	  $result = $this->parse($template);

	  // lucky: HACK: restore context
	  foreach ($stack as $key=>$v) $this->SetRef($key, $stack[$key] );
	  return $result;
  }
  
  function action_message( &$params ){
    $msgid = isset($params["_"]) && $params["_"] ? $params["_"] : $params[0];
    if ($this->msg) return $this->msg->Get( $msgid );
    else return $msgid;
  }
  function action_text( &$params )
  {
    $msgid = $params["_"] ? $params["_"] : $params[0];
    if (!$this->rh->db) 
    {
    	throw new Exception("Rockette::action_Text -> Db not implemented");
    }
    else 
    {
    	return $this->_Message( $msgid );
    }
  }
  
  //����� � ListObject
  function Loop( &$ITEMS, $tpl_root, $store_to='', $append=false, $implode=false ){
    //���������, ��������� �� ��� ListObject
    if(!(isset($this->list) && $this->list)){
      $this->rh->UseClass('ListObject');
      $this->list =& new ListObject( $this->rh, $ITEMS );
    }else
      $this->list->Set($ITEMS);
    //������
    $this->list->implode = $implode;
    return $this->list->parse( $tpl_root, $store_to, $append );
  }
  
// EOC{ TemplateEngine } 
}


?>
