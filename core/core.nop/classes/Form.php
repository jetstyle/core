<?php
/*

  ����-���������:
  * ���������, ��������� � ��������� ������� � ������� ����
  * ���������� � �� � ������ ������
  * see http://in.jetstyle.ru/rocket/rocketforms

  ����������� �����.

  Form( &$rh )
      - $rh              -- ������ �� RH, ��� ������

  -------------------

  // ��������������� �����

  * &AddField( $field_name, $config ) - ��������� ���� � �����. ������ ���� handshaking � ��������� ����
      - $field_name -- ��� �� ����
      - $config     -- ������������, ���.
  * &_AddField( &$field_object ) - ��������� ����, ������������ ��� ������

  * &AddButton( $button_config ) - ���������������� ������
      - $button_config -- ������-������ ������

  * _RegisterField( &$field ) - ���������� � $form->hash[$field->name] ������ �� ��� ����.
                                �� ��� �������� �������������

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

  // ��������� � ��

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
define( "FORM_EVENT_DELETE", "delete"); // ������� �� �� ��, ������� �� "success_url", if redirect  
define( "FORM_EVENT_AUTO",   "auto");   // insert/update based on $data_id  

class Form
{
   var $name; // ��� �����
   var $form_present_var = "__form_present";
   var $data_id_var = "__form_data_id";
   var $data_id=0;      // ������, ��������������� � ������. 0 -- ������ ��� �����
   var $hash=array();   // ����� ������� ������ ������� � �����
   var $fields=array(); // ����� ��������� ������ ������� � �����
   var $buttons=array();// ��������� "������"
   var $action; // ���� ������� �� ����� �����

   var $valid = true; // ���� ���������� �����

   var $default_config = array(
           "template_prefix"           =>"forms/",
           "template_prefix_button"    =>"forms/buttons.html:",
           "template_prefix_views"     =>"forms/views.html:",
           "template_prefix_wrappers"  =>"forms/",
           "template_prefix_interface" =>"forms/",
           "template_prefix_group"     =>"forms/",
           "template_form"                  =>"form.html:Form",
           "template_buttonlist"            =>"form.html:Buttons",
           "multipart"    =>  1,
           "auto_datetime"=>  1,  
           "auto_user_id" =>  1,  
           "id_field"     =>  "id",
           "active_field" =>  "active",
           "event_handlers_type" => "handlers/formevents", //IVAN
           "default_event" => FORM_EVENT_AUTO,
           "db_ignore" => false,
           "db_table"  => false,
           "fieldname_created_user_id"  => "_created_user_id",
           "fieldname_edited_user_id"   => "_edited_user_id",
           "fieldname_created_datetime" => "_created_datetime",
           "fieldname_edited_datetime"  => "_edited_datetime",
           // [optional] "success_url" => 
           // [optional] "cancel_url" =>
           // [optional] "on_before_event", "on_after_event"
                              );

   function Form( &$rh, $form_config=NULL )
   {
     $this->rh  = &$rh;
     $this->tpl = &$rh->tpl; // ����� ����� ����� ���� ����������� �� "��������" ���������� ������.
                             // ����� �������� ����
     
     $this->rh->UseClass("FormField"); // �� ��� ��������� �����������
     
     $this->action = $rh->ri->url;
                            
     if (!$form_config) $form_config = $this->default_config;
     else               Form::StaticDefaults($this->default_config, $form_config);

     $this->config = $form_config;

     // eventhandl.
     $a = array( "on_before_event", "on_after_event" );
     foreach($a as $v)
       if (isset($form_config[$v]) && !is_array($form_config[$v]))
       {
         $this->config[$v] = array();
         $this->config[$v][] = $form_config[$v];
       }
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
     $f = &new FormField( $this, $field_name, $config );
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

     // ����������� ������������ �����
     $uid = 0;
     do
     {
        //zharik@gmail.com: $_name should be initilazed before usage
        $_name = $this->config['db_table']? $this->config['db_table'] : 'form';
        if (!$uid) $this->name = $_name;
        else $this->name = $_name.'_'.$uid;
			 $uid++;
     }
     while (isset($this->rh->forms) && in_array($this->name, $this->rh->forms));
     $this->rh->forms[] = $this->name;

//     die(var_dump($_POST));
     //������� ���������� ����
     if (isset($_POST[$this->form_present_var]) && ($_POST[$this->form_present_var] == 'form_'.$this->name) && !$ignore_post)
     {
       $this->LoadFromPost( $_POST );

       // get event
       $event_name = $_POST["_event"];
       if ($_POST["_event2"])
         $event_name = $_POST["_event2"];
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
         // cancel
         if ($this->processed && !$this->success && isset($this->config["cancel_url"])) 
            $this->rh->Redirect( $this->config["cancel_url"] );
         // success
         if ($this->processed && $this->success && isset($this->config["success_url"])) 
         {
            die($this->config["success_url"]);
            $this->rh->Redirect( $this->config["success_url"] );
         }
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
          }else
          //��� ����� ���� ��������� �������
          {
            $this->_ExecEventHandler( $event, $this->rh->FindScript_($this->config["event_handlers_type"], $v) );
          }
        }
      }
   }
   
   //��������� ������� � �������� ������� ���������
   function _ExecEventHandler( $event, $event_handler )
   {
    if ($event_handler !== false){
      
      //������ ������ ��� �����������
      $rh =& $this->rh;
      include( $this->rh->FindScript("handlers","_enviroment") );
      $form =& $this;
      
      //�������� ����������
      include($event_handler);
    }
   }

   // �������� ��� ���� ����� � ��������� ���������
   function Reset()       
   {
     foreach($this->fields as $field) 
       $field->model->Model_SetDefault();
   }

   // ������� ����� � ���� ������� ���������
   function Parse()
   {
     $result = "";
     foreach($this->fields as $field) 
       $result .= $field->Parse();
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
     $form_name = isset($this->config["form_name"]) ? $this->config["form_name"] : 'form_'.$this->name;
     $this->tpl->Set( "form", 
      $this->rh->ri->Form( 
        $this->action, METHOD_POST, ' id="'.$form_name.'" name="'.$form_name.'" enctype="multipart/form-data" '
      )
     );
     $this->tpl->Set( "form_name", 'form_'.$this->name );
     $this->tpl->Set( "form_present", $this->form_present_var );
     $this->tpl->Set( "form_data_id", $this->data_id_var );
     $this->tpl->Set( "form_data_id_value", $this->data_id );
     $this->tpl->Set( "content", $content );
     $this->tpl->Set( "data_id", $this->data_id );
     $this->tpl->Set( "buttons", $this->_ParseButtons() );
     return $this->tpl->Parse( $this->config["template_prefix"].$this->config["template_form"]);
     
   }

   // ������� ������
   function _ParseButtons()
   {
     $result = array();
     foreach( $this->buttons as $button )
     {
       $this->tpl->SetRef( "*", $button );
       $result[]["BUTTON"] = $this->tpl->Parse( $this->config["template_prefix_button"].$button["template"] );
     }

     return $this->tpl->Loop( $result, $this->config["template_prefix"].$this->config["template_buttonlist"] );
   }

   // �������� �� �����
   function LoadFromPost( $post_data )           
   { 
     $this->AssignId( @$post_data[ $this->data_id_var ] ); //IVAN

     foreach($this->fields as $k=>$field) 
       $this->fields[$k]->LoadFromPost( $post_data );
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

     if ($is_error) $this->rh->debug->Error_R( $dump_hash );
     else           $this->rh->debug->Trace_R( $dump_hash );
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
       case FORM_EVENT_DELETE:
                              $this->DbDelete();
                              $this->success   = true;
                              $this->processed = true;
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
      $this->rh->Redirect( $this->rh->ri->Href($this->rh->ri->url) );
     else
     {
       $this->ResetSession(); // ���� ������� ����������, �� ������ ����������
     }
   }

   // ������� � ��
   function DbInsert()
   {
      if (!$this->config["db_table"]) 
        if ($this->config["db_ignore"]) return;
        else $this->rh->debug->Error("[Form]: *db_table* form config option is not set.");

      $fields = array();
      $values = array();
      foreach($this->fields as $k=>$v)
        $this->fields[$k]->DbInsert( $fields, $values );

      $this->_DbAuto( $fields, $values, true );

      foreach($values as $k=>$v)
        $values[$k] = $this->rh->db->Quote($values[$k]);

      $sql = "insert into ".$this->config["db_table"];
      if (sizeof($fields) > 0) 
        $sql.=" (".implode(",",$fields).") VALUES (".implode(",",$values).")";
        
      //$this->rh->debug->Error( $sql );
      $this->data_id = $this->rh->db->Insert($sql);

      foreach($this->fields as $k=>$v)
        $this->fields[$k]->DbAfterInsert( $this->data_id );
   }
   function DbUpdate( $data_id = NULL )
   {
      if (!$this->config["db_table"]) 
        if ($this->config["db_ignore"]) return;
        else $this->rh->debug->Error("[Form]: *db_table* form config option is not set.");

      if ($data_id == NULL) $data_id = $this->data_id;

      $fields = array(); 
      $values = array();
      foreach($this->fields as $k=>$v)
        $this->fields[$k]->DbUpdate( $data_id, $fields, $values );

      $this->_DbAuto( $fields, $values );

      $this->_DbUpdate( $fields, $values );

      foreach($this->fields as $k=>$v)
        $this->fields[$k]->DbAfterUpdate( $data_id );
   }
   function _DbUpdate ( &$fields, &$values )
   {
      $fields_values = array();
      foreach($fields as $k=>$v)
        $fields_values[] = $v." = ".$this->rh->db->Quote($values[$k]);

      $sql = "update ".$this->config["db_table"].
             " set ".implode(",",$fields_values)." where ".
             $this->config["id_field"]."=".$this->rh->db->Quote($this->data_id);
      //$this->rh->debug->Error( $sql );
      if (sizeof($fields) == 0) return false;
      $this->rh->db->Query($sql);

   }
   function _DbAuto( &$fields, &$values, $is_insert=false )
   {
      $user = $this->rh->principal->id;
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
   function Load( $data_id = NULL )  
   {
     if (!$this->config["db_table"])
       if ($this->config["db_ignore"]) return;
       else $this->rh->debug->Error("[Form]: *db_table* form config option is not set.");

     if ($data_id == NULL) $data_id = $this->data_id;
     $sql = "select * from ".$this->config["db_table"]." where ".
             $this->config["id_field"]."=".$this->rh->db->Quote($data_id);
     $data = $this->rh->db->QueryOne( $sql );
     if ($data == false) 
     {
       $this->data_id = 0;
       return;
     }
     foreach($this->fields as $k=>$v)
       $this->fields[$k]->DbLoad( $data );
   }

   // �������� �� ��
   function DbDelete( $data_id = NULL )
   {
     if (!$this->config["db_table"]) 
       if ($this->config["db_ignore"]) return;
       else $this->rh->debug->Error("[Form]: *db_table* form config option is not set.");

     if ($data_id == NULL) $data_id = $this->data_id;
     foreach($this->fields as $k=>$v)
       $this->fields[$k]->DbDelete( $data_id );

     $sql = "delete from ".$this->config["db_table"]." where ".
             $this->config["id_field"]."=".$this->rh->db->Quote($data_id);
     $this->rh->db->Query( $sql );
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


   var $_inner_name_counter = 0;
   function _NextInnerName()
   {
     $this->_inner_name_counter++;
     return "__inner_".$this->_inner_name_counter;
   }

// EOC{ Form }
}


?>
