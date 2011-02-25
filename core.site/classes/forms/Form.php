<?php
/*

  ����-���������:
  * ���������, ��������� � ��������� ������� � ������� ����
  * ���������� � �� � ������ ������
  * see http://in.jetstyle.ru/rocket/rocketforms

  ����������� �����.

  Form( $config )

  -------------------

  // ��������������� �����

  * &AddField( $field_name, $config ) - ��������� ���� � �����. ������ ���� handshaking � ��������� ����
      - $field_name -- ��� �� ����
      - $config     -- ������������, ���.
  * &_AddField( &$field_object ) - ��������� ����, ������������ ��� ������

  * &AddButton( $button_config ) - ���������������� ������
      - $button_config -- ������-������ ������

  * _RegisterField( &$field ) - ���������� � $form->hash[$field->name] ������ �� ��� ����.
                                �� ��� �������� ?����������?�

  // ���������� ����� !! ����� �������

  * Handle( $ignore_post=false, $ignore_load=false, $ignore_validator=false, $ignore_session=false )
      - $ignore_* -- ������������ �� ��� ���� ����� �������� �����������
      - false, ���� ��������� -- �� ����������� ����� (����� �������, ���� ��� ���������, �� ������� ���������

  * ProcessEvent( $event_code ) -- proceed event as we hit one of the buttons
                                   useful for programmatical control

  * _ExecEventHandler($event,$event_handler) - ��������� ������� � �������� ���� ���������
      - $event -- ������� �������
      - $event_handler -- ������ ���� �� ����� ��������

  // ��������� �����

  - AssignId( $data_id ) - ������ ����� � ������������ ������ � ��

  * Load( $data_id=NULL ) - ��������� ����� �� ��
      - $data_id -- ���� NULL, �� ���� this->data_id

  * LoadFromArray( $a ) - ��������� ����� �� �������
      - $a -- ������, �� �������� ���������

  * Reset() - ���������� ����� � "�������������" ���������

  // ?�������� � ��

  - DbDelete( $data_id=NULL ) -- ������� �����. ������ �� ��,
      - true, if success
      - ������ �������� ������ ������ ������� DbDelete ���� �����
  - DbInsert()                -- ��������� ������� ��������� ����� � ��, ���������� $data_id
  - DbUpdate( $data_id=NULL ) -- ���������� ������ � ��, ���������� $data_id
  - _DbUpdate( &$fields, &$values ) -- ��������� sql-������ � ���������� ��� � ��
  - _DbAuto( &$fields, &$values ) -- ��������� � $fields, $values ������������� ��������������� ����

  // �������, ������ ����� �������� ����������

  * Parse()
  * ParsePreview()

  // ��������������� ������

  * StaticDefaults( $default_config, &$supplied_config ) - ��������� �����, ������������
                                                            supplied_config �� ����������
                                                            (��������� ��� ����, �������
                                                            ����������� � ���������
  * _ParseWrapper( $content )
  * _ParseButtons()


================================================================== v.0 (kuso@npj)
*/
define( "FORM_EVENT_OK",     "ok");     // ������ �� ������, ������� �� "success_url", if redirect
define( "FORM_EVENT_CANCEL", "cancel"); // ������, ������� �� "cancel_url", if redirect
define( "FORM_EVENT_RESET",  "reset");  // ����� ��������� ����� � ����������
define( "FORM_EVENT_INSERT", "insert"); // ������� � ��, ������� �� "success_url", if redirect
define( "FORM_EVENT_UPDATE", "update"); // ������ � ��, ������� �� "success_url", if redirect
define( "FORM_EVENT_UPDATE_CLIENT", "update_client"); // ������ � ��, ������� �� "success_url", if redirect
define( "FORM_EVENT_DELETE", "delete"); // ������� �� �� ��, ������� �� "success_url", if redirect
define( "FORM_EVENT_AUTO",   "auto");   // insert/update based on $data_id

class Form
{
    var $name; // ��� �����
    var $form_present_var = "__form_present";
    var $data_id_var      = "__form_data_id";
    var $data_id          = 0;      // ������, ��������������� � ������. 0 -- ������ ��� �����
    var $hash             = array();   // ����� ������� ������ ������� � �����
    var $fields           = array(); // ����� ��������� ������ ������� � �����
    var $buttons          = array();// ��������� "������"
    var $action; // ���� ������� �� ����� �����
 
    var $valid = true; // ���� ���������� �����

    public function __construct()
    {
        Finder::UseClass("forms/FormField"); // �� ��� ��������� �����������

        $args = func_get_args();
        array_unshift($args, 'default');
        $formConfig = array();
        foreach ($args as $arg)
        {
            if (is_string($arg))
            {
                $ymlFile  = Finder::findScript('classes/forms', $arg, 0, 1, 'yml');
                if ( $ymlFile )
                {
                    $arg = YamlWrapper::load($ymlFile);
                }
                else
                {
                    throw new FileNotFoundException('classes/forms/'.$arg.'.yml');
                }
            }
            $formConfig = self::mergeConfigs($formConfig, $arg);
        }

        if ($formConfig['template_form'])
        {
            $parts = explode(":", $formConfig['template_form']);
            if (count($parts) == 1)
            {
                $formConfig['template_form'] = "form.html:".$formConfig['template_form'];
            }
        }

        $this->config = $formConfig;

        $a = array( "on_before_event", "on_after_event" );
        foreach($a as $v)
        {
            if (isset($formConfig[$v]) && !is_array($formConfig[$v]))
            {
                $this->config[$v] = array();
                $this->config[$v][] = $formConfig[$v];
            }
        }
        
        if ($this->config['form_name'])
        {
            $this->name = $this->config['form_name'];
        }
        else if ($this->config['db_table'])
        {
            $this->name = $this->config['db_table'];
        }
        else
        {
            if (!Config::get('last_form_id'))
            {
                Config::set('last_form_id', 1);
            }
            $this->name = 'form'.Config::get('last_form_id');
            Config::set('last_form_id', Config::get('last_form_id')+1);
        }
        
		if( $id = isset($this->config["id"]) ? $this->config["id"] : false )
        {
			$this->AssignId( $id );
        }
		else
        {
			if( $id = isset($_REQUEST[ '_id' ]) ? $_REQUEST[ '_id' ] : false )
            {
				$this->form->AssignId( $id );
            }
        }

		$this->AddFields( $this, $this->config["fields"] );
		$this->AddButtons( $this, $this->config["buttons"] );
    }

   // ������������� "�������� ��-���������"
   function StaticDefaults( $default_config, &$supplied_config )
   {
     foreach( $default_config as $k=>$v )
       if (!isset($supplied_config[$k])) $supplied_config[$k] = $v;
   }

    // �������� ����
    function &AddField( $field_name = NULL, $config )
    {
        if ($config['extends_from'])
        {
            $className = $config['extends_from'];

            if (Finder::findScript('classes/forms/components/', $className))
            {
                Finder::useClass('forms/components/'.$className);
                $className = $className;
            }
            else
            {
                $className = 'FormField';
            }
        }
        else
        {
            $className = 'FormField';
        }
        $f = new $className( $this, $field_name, $config );
        return $this->_AddField($f);
    }
    
   function &_AddField( &$field_object )
   {
     $this->fields[] = &$field_object;
     $field_object->_LinkToForm( $this );
     return $field_object;
   }

   // �������� ������
   function &AddButton( $button_config )
   {
     $this->buttons[$button_config["title"]] = $button_config;
     return $this->buttons[$button_config["title"]];
   }

   // ����� �������� ��������� --------------------------------------------------------
   //zharik: ��, ������ ��� �� ����� �� � �������� 8))
   function Handle( $ignore_post     =false,  $ignore_load   =false,
                    $ignore_validator=false,  $ignore_session=false )
   {
     $processed = false;

     //������������� �������� �����
     if ($this->data_id && !$ignore_load) $this->Load();  // ������� ���������
     if (!$this->data_id || $ignore_load) $this->Reset(); // ������������� default-��������
     if (!$ignore_session) $this->FromSession();

     $postData = null;
     if (isset($_POST[$this->form_present_var]) && ($_POST[$this->form_present_var] == $this->name))
     {
         $postData = &$_POST;
     }
     elseif (isset($_GET[$this->form_present_var]) && ($_GET[$this->form_present_var] == $this->name))
     {
         $postData = &$_GET;
     }

     //������� ���������� ����
     if (is_array($postData) && !$ignore_post)
     {
       $this->LoadFromPost( $postData );

       // get event
       $event_name = $postData["_event"];
       /*
       if ($_POST["_event2"])
         $event_name = $_POST["_event2"];*/
       $event = $this->buttons[$event_name];

       if (!$event) $event = $this->config["default_event"];

       if (!is_array($event)) $event = array( "event" => $event );

       if ($ignore_validator
           || ($event["event"] == FORM_EVENT_CANCEL)
           || ($event["event"] == FORM_EVENT_RESET)
           || ($event["event"] == FORM_EVENT_DELETE)
           || $this->Validate()
          )
       {
         $processed = 1;
         if (!$ignore_session) $this->ToSession();

         $this->_ProcessEvent( $event );

         // redirect
         // delete
         if ($this->processed && $this->success && $this->deleted )
         {
            Controller::redirect( $this->config["delete_url"] ? $this->config["delete_url"] : $this->config["success_url"] );
         }
         // cancel
         if ($this->processed && !$this->success && isset($this->config["cancel_url"]))
            Controller::redirect( $this->config["cancel_url"] );
         // success
         if ($this->processed && $this->success && isset($this->config["success_url"]))
            Controller::redirect( $this->config["success_url"] );

         $processed = 0;
       }
     }
     if (!$processed)
       $result = $this->Parse();
     else $result = false;

          return $result;
   }

   function ProcessEvent( $event_code )
   {
     $event = false;
     foreach( $this->buttons as $k=>$v )
       if ($v["event"] == $event_code) { $event = $v; break; }

     if (!$event && ($event_code != $this->config["default_event"]))
       return $this->ProcessEvent( $event_code );

     return $this->_ProcessEvent( $event );
   }
   function _ProcessEvent( $event )
   {
     // before
     $this->_ChooseEventHandler( $event, "on_before_event", "OnBeforeEventForm" );

     // event
     $this->HandleEvent( $event );

     // after
     $this->_ChooseEventHandler( $event, "on_after_event", "OnAfterEventForm" );
   }

   //��������, ����� ���������� ������� ���������
   function _ChooseEventHandler( $event, $handler, $default_method )
   {
      if (isset($this->config[$handler])){
        foreach($this->config[$handler] as $k=>$v){
          //��� ����� ���� ��������� �������
          //��� ��� ����� ���� ������ � ���� �������� �������

          if (is_callable($this->config[$handler][$k])){
            call_user_func($this->config[$handler][$k], $event, $this);
          }else
          //��� ����� ���� ������ � ������� �� ���������
          if ( is_callable( array($this->config[$handler][$k],$default_method) ) ){
            $this->config[$handler][$k]->$default_method( $event, $this );
          }
        }
      }
   }

   // �������� ��� ���� ����� � ��������� ���������
   function Reset()
   {
     foreach($this->fields as $field)
       $field->Model_SetDefault();
   }

   // ������� ����� � ���� ������� ���������
   function Parse()
   {
     $result = "";
     foreach($this->fields as $field){
       $result .= $field->Parse();
       }


     return $this->_ParseWrapper( $result );
   }

   // ������� ����� "������ ��� ������", ��� ������
   function ParsePreview()
   {
     $result = "";
     foreach($this->fields as $field)
       $result .= $field->Parse( "readonly" );
     return $result;
   }

   // ������� ������ ���������: ������ ���, ������
   function _ParseWrapper( $content )
   {
	 $tpl = &Locator::get('tpl');
     $form_name = $this->name;
     $tpl->set(
     	"form",
     	"<form action=\"".$this->config['action']."\" ".
     		"method=\"".( $this->config["form_method"] ? $this->config["form_method"] : RequestInfo::METHOD_POST )."\" ".
     		"id=\"".$form_name."\"".
     		"name=\"".$form_name.'" '.
     		($this->config["form_class"] ? 'class="'.$this->config["form_class"].'"' : '' ).
     		($this->config["form_onsubmit"] ? "onsubmit='".$this->config["form_onsubmit"]."'" : '' ).
     		' enctype="multipart/form-data"> '/*. RequestInfo::pack(RequestInfo::METHOD_POST)*/
     );

     $tpl->set( "form_name", $form_name );
     $tpl->set( "form_present", $this->form_present_var );
     $tpl->set( "form_data_id", $this->data_id_var );
     $tpl->set( "form_data_id_value", $this->data_id );
     $tpl->set( "content", $content );
     $tpl->set( "data_id", $this->data_id );
     $tpl->set( "buttons", $this->_ParseButtons() );

     //��������� ������ �� �����������
     $tpl->set( "buttons_left",  $this->_ParseButtons(0) );
     $tpl->set( "buttons_right", $this->_ParseButtons(1) );

     return $tpl->Parse( $this->config["template_prefix"].$this->config["template_form"]);

   }

   // ������� ������
   function _ParseButtons($index=null)
   {
     $tpl = &Locator::get('tpl');
     $result = array();
     foreach( $this->buttons as $button )
     {
       $tpl->SetRef( "*", $button );

       //��������� ����������� ������ � ������ �����
       if (!empty($button['store_to']))
       {
         $tpl->Parse( $this->config["template_prefix_button"].$button["template"], $button['store_to'] );
        }
       else
           $result[]["BUTTON"] = $tpl->Parse( $this->config["template_prefix_button"].$button["template"] );
     }

     //��������� ������ ���� ������
     if ($index!==null)
     {
       $tpl->setRef('*', $result[$index]);
       $ret = $tpl->parse($this->config["template_prefix"].$this->config["template_buttonlist"]."_Item");

       return  $ret;
     }
     else
       return $tpl->set('buttons', $result, $this->config["template_prefix"].$this->config["template_buttonlist"] );
   }

   // �������� �� �����
   function LoadFromPost( $post_data )
   {
     $this->AssignId( @$post_data[ $this->data_id_var ] ); //IVAN

     foreach($this->fields as $k=>$field)
     {
       $this->fields[$k]->LoadFromPost( $post_data );
     }
   }

   // ��������� ���� ����� �����
   function Validate()
   {
     $this->valid = true;
     foreach($this->fields as $k=>$field)
       $this->valid = $this->fields[$k]->Validate() && $this->valid; // �����, ��� ������ � ����� �������
     return $this->valid;
   }

   // ���� �����
   function _Dump( $is_error=1 )
   {
     $dump_hash = array();
     foreach( $this->fields as $k=>$field )
      $dump_hash[ $field->name ] = $field->_Dump();
   }

   // ������ � ������
   function FromSession()
   {
     $key = "form_".$this->config["db_table"];
     $session_storage = isset($_SESSION[$key]) ? $_SESSION[$key] : "";
     if (!is_array($session_storage)) return; // no session -- no restore
     foreach( $this->fields as $k=>$field )
      $this->fields[$k]->FromSession( $session_storage );
   }
   function ToSession()
   {
     $session_storage = array();
     foreach( $this->fields as $k=>$field )
      $this->fields[$k]->ToSession( $session_storage );
     $_SESSION[ "form_".$this->config["db_table"] ] = $session_storage;
   }
   function ResetSession()
   {
     $_SESSION[ "form_".$this->config["db_table"] ] = "";
   }

   // ��������� �������, ��� (�������/��������������)
   function HandleEvent( $event = FORM_EVENT_AUTO )
   {
     if (is_array($event)) $_event = $event["event"];
     else                  $_event = $event;

     if ($_event == FORM_EVENT_AUTO)
     {
       if ($this->data_id) $_event = FORM_EVENT_UPDATE;
       else                $_event = FORM_EVENT_INSERT;
     }

     //$this->deleted = false;
     switch( $_event )
     {
       case FORM_EVENT_INSERT:
                              $this->DbInsert();
                              $this->success   = true;
                              $this->processed = true;
                              break;
       case FORM_EVENT_UPDATE:
                              $this->DbUpdate();
                              $this->success   = true;
                              $this->processed = true;
                              break;
       case FORM_EVENT_UPDATE_CLIENT:
                              $this->DbUpdate();
                              $this->success   = true;
                              $this->processed = true;
                              break;
       case FORM_EVENT_DELETE:
                              if ($this->deleted)
                                  break;
                              $this->DbDelete();
                              $this->success   = true;
                              $this->processed = true;
                              $this->deleted = true;
                              break;

       case FORM_EVENT_RESET:
                              $this->ResetSession();
                              $this->Reset();
                              $this->ToSession();
                              $this->success   = false;
                              $this->processed = false; // returning to form again
                              break;


       case FORM_EVENT_CANCEL:
                              $this->success   = false;
                              $this->processed = true;
                              break;

       case FORM_EVENT_OK:
       default:               $this->success   = true;
                              $this->processed = true;
     }
     $this->processed_event = $event;

     if (!$this->processed)
		Controller::redirect();
     else
		$this->ResetSession(); // ���� ������� ����������, �� ������ ����������
   }

    // ������� � ��
    function dbInsert()
    {
        if (!$this->config["db_table"] && !$this->config["db_model"])
            if ($this->config["db_ignore"])
                return;
            else
                throw new JSException("[Form]: *db_table* form config option is not set.");

        $fields = array();
        $values = array();
        foreach($this->fields as $k=>$v)
            $this->fields[$k]->dbInsert( $fields, $values );

        $this->_dbAuto( $fields, $values, true );

        if ($this->config["db_table"])
        {
            $db = &Locator::get('db');
            foreach($values as $k=>$v)
                $values[$k] = $db->quote($values[$k]);
            $sql = "insert into ".Config::get('db_prefix').$this->config["db_table"];
            if (sizeof($fields) > 0)
                $sql.=" (".implode(",",$fields).") VALUES (".implode(",",$values).")";
            $this->data_id = $db->insert($sql);
        }
        else
        {
            if (is_string($this->config["db_model"]))
                $model = DBModel::factory($this->config["db_model"]);
            else
                $model = $this->config["db_model"];
            $data = array_combine($fields, $values);
            $this->data_id = $model->insert($data);
        }

        foreach($this->fields as $k=>$v)
            $this->fields[$k]->dbAfterInsert( $this->data_id );
    }

    function dbUpdate( $dataId = NULL )
    {
        if (!$this->config["db_table"] && !$this->config["db_model"])
            if ($this->config["db_ignore"])
                return;
            else
                throw new JSException("[Form]: *db_table* form config option is not set.");

        if ($dataId == NULL) $dataId = $this->data_id;

        $fields = array();
        $values = array();
        foreach($this->fields as $k=>$v) {
            $this->fields[$k]->dbUpdate( $dataId, $fields, $values );
        }

        $this->_dbAuto( $fields, $values );

        if ($this->config["db_table"])
        {
            $this->_DbUpdate( $fields, $values );
        }
        else
        {
            if (is_string($this->config["db_model"]))
                $model = DBModel::factory($this->config["db_model"]);
            else
                $model = $this->config["db_model"];
            $data = array_combine($fields, $values);

            $model->update($data, '{'.$this->config["id_field"].'} = '.Locator::get('db')->quote($dataId));
        }

        foreach($this->fields as $k=>$v)
            $this->fields[$k]->DbAfterUpdate( $dataId );
    }

    function _dbUpdate ( &$fields, &$values )
    {
        $fields_values = array();
        foreach($fields as $k=>$v)
            $fields_values[] = $v." = ".Locator::get('db')->quote($values[$k]);

        $sql = "update ".$this->config["db_table"].
               " set ".implode(",",$fields_values)." where ".
               $this->config["id_field"]."=".Locator::get('db')->quote($this->data_id);
        if (sizeof($fields) == 0) return false;
        Locator::get('db')->execute($sql);
   }

    function _dbAuto( &$fields, &$values, $is_insert=false )
    {
        $user = Locator::get('principal')->getId();
        $dt = date("Y-m-d H:i:s");
        if ($this->config["auto_user_id"])
        {
            if ($is_insert)
            {
                $fields[] = $this->config["fieldname_created_user_id"];//"_created_user_id";
                $values[] = $user;
            }
            $fields[] = $this->config["fieldname_edited_user_id"];//"_edited_user_id";
            $values[] = $user;
        }
        if ($this->config["auto_datetime"])
        {
            if ($is_insert)
            {
                $fields[] = $this->config["fieldname_created_datetime"];//"_created_datetime";
                $values[] = $dt;
            }
            $fields[] = $this->config["fieldname_edited_datetime"];//"_edited_datetime";
            $values[] = $dt;
        }
    }

    // �������� �� ��
    function load( $dataId = NULL )
    {
        if (!$this->config["db_table"] && !$this->config["db_model"])
            if ($this->config["db_ignore"])
                return;
            else
                throw new JSException("[Form]: *db_table* form config option is not set.");

        if ($dataId == NULL) $dataId = $this->data_id;

        if ($this->config["db_table"])
        {
            $sql = "select * from ".$this->config["db_table"]." where ".
                    $this->config["id_field"]."=".Locator::get('db')->quote($dataId);
            $data = Locator::get('db')->queryOne( $sql );
        }
        else
        {
            $model = $this->getModel();
            $data = $model->loadOne('{'.$this->config["id_field"].'} = '.Locator::get('db')->quote($dataId))->getArray();
        }

        if ($data == false)
        {
            $this->data_id = 0;
            return;
        }
        foreach($this->fields as $k=>$v)
            $this->fields[$k]->DbLoad( $data );
    }

   function getModel(){
        if (!$this->db_model)
        {
            if (is_string($this->config["db_model"]))
                $model = DBModel::factory($this->config["db_model"]);
            else
                $model = $this->config["db_model"];

            $this->db_model = $model;
        }
        
        return $this->db_model;
   }

   // �������� �� ��
   function DbDelete( $data_id = NULL )
   {
     if (!$this->config["db_table"])
      	if ($this->config["db_ignore"]) return;
      	  else throw new JSException("[Form]: *db_table* form config option is not set.");

     if ($data_id == NULL) $data_id = $this->data_id;
     foreach($this->fields as $k=>$v)
       $this->fields[$k]->DbDelete( $data_id );

     $sql = "delete from ".$this->config["db_table"]." where ".
             $this->config["id_field"]."=".Locator::get('db')->quote($data_id);
     Locator::get('db')->query( $sql );
   }

   // �������� �� �������
   function LoadFromArray( $data )
   {
     foreach($this->fields as $k=>$v)
       $this->fields[$k]->LoadFromArray( $data );
   }

   // ������ ����� � ������������ ������ � ��
   function AssignId( $data_id )
   {
     $this->data_id = $data_id;
   }

    public function &getFieldByName($name)
    {
        $resultField = null;
        foreach ($this->fields AS $k => $field)
        {
            if ($field->name == $name)
            {
                $resultField = $this->fields[$k];
                break;
            }
            elseif ($resultField = &$field->getFieldByName($name))
            {
                break;
            }
        }
        return $resultField;
    }


   var $_inner_name_counter = 0;
   function _NextInnerName()
   {
     $this->_inner_name_counter++;
     return "__inner_".$this->_inner_name_counter;
   }

    //code from EasyForm
    public static function mergeConfigs($configBase, $configNew)
    {
        foreach( $configNew as $k => $v )
        {
            if (is_array($v) && isset($configBase[$k]))
            {
                $configBase[$k] = self::mergeConfigs($configBase[$k], $v);
            }
            else if ($k !== 'extends_from')
            {
                $configBase[$k] = $v;      
            }
          
        }
        return $configBase;
    }
    
    var $wrapper_tpl = array(
		"label,number,radio,select,string,password,checkbox" => array( "wrapper.html:Div", "wrapper.html:Row" ),
		"file,image"             => array( "wrapper.html:Div", "wrapper.html:Row" ),
		"date,date_optional"     => array( "wrapper.html:Div", "wrapper.html:Row" ),
		"textarea,htmlarea"      => array( "wrapper.html:Div", "wrapper.html:RowSpan" ),
	);
    
    //��������� ���� � ����� ��� ������
	protected function addFields(&$form, $config, $is_field=false) {
		//��� ��������� ����
        if ($config)
        {
            foreach ($config AS $name => $rec)
            {
                //��������� ������ ��� ����
                if ( is_array($rec) )
                {
                    $pack_name = $rec['extends_from'];
                    $conf = $rec;
                    /*if (isset($rec[1]))
                        if (is_array($rec[1])) $conf = $rec[1];
                            else $conf = array( "model_default" => $rec[1] );
                    else $conf = array();*/
                }
                else
                {
                    $pack_name = $rec;
                    $conf = array();
                }
                    
                //���������� ������ ��� ����
                $conf = $this->ConstructConfig( $pack_name, $conf, false, $name );

                if ($conf)
                {
                    //���������� wrapper_tpl
                    if (!isset($conf["wrapper_tpl"]))
                        foreach ($this->wrapper_tpl as $k=>$v)
                        {
                            if (in_array($pack_name, explode(",",$k)))
                            {
                                $conf["wrapper_tpl"] = $v[ $is_field ? 1 : 0 ];
                                break;
                            }
                        }

                    //������ ����
                    if (method_exists($form, 'Model_AddField'))
                    {
                        $field =& $form->Model_AddField( $name, $conf );
                    }
                    else
                    {
                        $field =& $form->AddField( $name, $conf );
                    }
                    //���� ������ ����� ������, ������������ ��������
                    if ($conf['fields'])
                    {
                        $this->AddFields($field, $conf['fields'], true);
                    }
                }
            }
        }
	}

    //��������� ������ � �����
    function AddButtons( &$form, $config )
    {
        //��� ��������� ������
        foreach($config as $btn => $rec)
        {
            //��������� ������ ��� ������
            $rec_cfg = false;
            if( is_array($rec) && isset($rec[1]) && isset($rec[0]) )
            {
                $rec_cfg = $rec[1];
                $rec = $rec[0];
            }
            else if ( is_array($rec) )
            {
                $rec_cfg = $rec;
                $rec = $btn; 
            }
      
            $conf = $this->ConstructConfig( "button_".$rec, $rec_cfg, $rec );
      
            //������ ������
            $field =& $form->AddButton( $conf );
        }
    }

	//��������� ������ �� ������ ������
	function constructConfig($conf_name, $_config=false, $is_btn=false, $field_name="")
	{
		//������������ ������
		$config = array();
                
        //���� ��� ����� ������, �������� � ������� ����� default_packages
        if ( !$conf_name )
        {
            $conf_name = $this->config["default_packages"][$field_name]["extends_from"];
            if (isset($this->config["default_packages"][$field_name]) && !$conf_name)
            {
                Debug::trace("PACK $field_name:  ".$conf_name);
                return false;                
            }
            else
                $_config = self::mergeConfigs($this->config["default_packages"][$field_name], $_config);
        }

        //���� ��� ��� ��� ����� ������, ���������� ��� � ����� ����
        if ( !$conf_name )
            $conf_name = $field_name;

        //merging packages
        while ($filename = Finder::findScript("classes","forms/packages/".$conf_name))
        {
            $currentConfig = $config;
            include( $filename );
            $currentConfig['extends_from'] = $config['extends_from'];
            $config = array_merge($config, $currentConfig);
            if ($conf_name == $config['extends_from'])
            {
                break;   
            }
            else
            {
                $conf_name = $config['extends_from'];
            }
        }
        if ($conf_name && !Finder::findScript("classes","forms/components/".$conf_name))
        {
            throw new JSException('Failed to find form package "'.$conf_name.'" for field "'.$field_name.'"');
        }

		if (isset($_config["easyform_override"]))
			foreach( $_config["easyform_override"] as $v )
				unset($config[$v]);

		//���������� ����� �� ������ � ������
		if (is_array($_config))
			$config = self::mergeConfigs($config, $_config);

		return $config;
	}
}

?>

