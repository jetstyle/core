<?php
  // ������ �������� ������
  // (*) -- ������ ��������
  // (+) -- �������� ����� ������, by-default ���������� � production single-site version
  // (!) -- ����������� ������!

  
  // ���������� � �������� ���������� ������
  $this->DIRS[] = dirname(__FILE__).'/';
  
  // ��������� ���������� ������
  $this->tpl_markup_level  = 0; // TPL_MODE_CLEAN     (*)
  $this->tpl_compile       = 1; // TPL_COMPILE_SMART  (*)
  $this->tpl_root_dir      = "../"; // (+) or "" or "themes/" -- ��� ����� �����
  $this->tpl_root_href     = "/";   // (+) or "/themes/"         -- ��� ��������� �� URL �� ���
  $this->tpl_skin          = "";    // (*) for no-skin-mode which is default
  $this->tpl_skin_dirs     = array( "css", "js", "images" ); // -- ����� �������� �������

  // ��������� ���������� ������
  $this->tpl_action_prefix      = "rockette_action_";
  $this->tpl_template_prefix    = "rockette_template_";
  $this->tpl_template_sepfix    = "__";
  $this->tpl_action_file_prefix   = "@@"; 
  $this->tpl_template_file_prefix = "@";
  $this->tpl_cache_prefix = "@";  // � ����� �������� ���������� � ���� ��� ���������� TE
  $this->tpl_prefix = "{{";
  $this->tpl_postfix = "}}";
  $this->tpl_instant = "~";
  $this->tpl_construct_action   = "!";    // {{!text Test}}
  $this->tpl_construct_action2  = "!!";   // {{!!text}}Test{{!!/text}}
  $this->tpl_construct_if       = "?";    // {{?var}} or {{?!var}}
  $this->tpl_construct_ifelse   = "?:";   // {{?:}} 
  $this->tpl_construct_ifend    = "?/";   // {{?/}} is similar to {{/?}}
  $this->tpl_construct_object   = "#.";   // {{#obj.property}}
  $this->tpl_construct_tplt     = "TPL:"; // {{TPL:Name}}...{{/TPL:Name}}
  $this->tpl_construct_tplt2    = ":"; // {{:Name}}...{{/:Name}}   -- ru@jetstyle ����� ����� TPL � �����
  $this->tpl_construct_comment  = "#";    // <!-- # persistent comment -->

  $this->tpl_instant_plugins = array( "dummy" ); // plugins that are ALWAYS instant

  $this->shortcuts = array(
                            "=>" => array("=", " typografica=1"),
                            "=<" => array("=", " strip_tags=1"),
                            "+>" => array("+", " typografica=1"),
                            "+<" => array("+", " strip_tags=1"),
                            "*" => "#*.",
                            "@" => "!include ",
                            "=" => "!text ",
                            "+" => "!message ",
                        );

  // message set defaults
  $this->msg_default = "ru"; 

  // ������ ���������
  $this->cache_dir              = "zcache/"; // (+) or "../project.zcache/" -- ���� ������ ���

  // ��� ��������� ���������: ���������� ��
  $this->lib_href_part          = "libs"; // ��� ������� �� ��������. ��� �����!
  $this->lib_dir                = $this->lib_href_part; 

  $this->magic_word             = "I luv rokket"; // ���������� ����� ��� ��������� ������ ������������������

  // ������ ����
  $this->url_allow_direct_handling = false; // �������� ���������� URL � ���� �� ��������

  // ��������� ���
  $this->cookie_prefix      = ""; // (+) ������� ���� ���. ��������� ���������� ������ �� ����� ������ ���������� ������������ � �����
  $this->cookie_expire_days = 60; // ������� ���� ��������� ������������ ����
  

?>