<?php
/*

  Форм-процессор:
  * генерация, обработка и валидация простых и сложных форм
  * сохранение в БД и чтение оттуда
  * see http://in.jetstyle.ru/rocket/rocketforms

  Управляющий класс.

  Form( $config )

  -------------------

  // Конструирование формы

  * &AddField( $field_name, $config ) - Привязать поле в форму. Делает весь handshaking с созданием поля
      - $field_name -- что за поле
      - $config     -- конфигурация, ага.
  * &_AddField( &$field_object ) - Привязать поле, существующее как объект

  * &AddButton( $button_config ) - зарегистрировать кнопку
      - $button_config -- массив-конфиг кнопки

  * _RegisterField( &$field ) - записывает в $form->hash[$field->name] ссылку на это поле.
                                НЕ ДЛЯ ВНЕШНЕГО ИСПОЛЬЗОВАНИЯ

  // Процессинг формы !! Самое главное

  * Handle( $ignore_post=false, $ignore_load=false, $ignore_validator=false, $ignore_session=false )
      - $ignore_* -- игнорировать те или иные этапы сценария процессинга
      - false, если результат -- не отпарсенная форма (иными словами, если нет редиректа, но событие произошло

  * ProcessEvent( $event_code ) -- proceed event as we hit one of the buttons
                                   useful for programmatical control

  * _ExecEventHandler($event,$event_handler) - выполняет хэндлер в отдельно зоне видимости
      - $event -- текущее событие
      - $event_handler -- полный путь до файла хэндлера

  // Настройка формы

  - AssignId( $data_id ) - ставит форме в соответствие строку в БД

  * Load( $data_id=NULL ) - загружает форму из БД
      - $data_id -- если NULL, то берёт this->data_id

  * LoadFromArray( $a ) - загружает форму из массива
      - $a -- массив, из которого загружать

  * Reset() - Сбрасывает форму в "незаполненное" состояние

  // Изменение в БД

  - DbDelete( $data_id=NULL ) -- удаляет соотв. строку из БД,
      - true, if success
      - прежде удаления строки должно вызвать DbDelete всех полей
  - DbInsert()                -- вставляет текущее состояние формы в БД, возвращает $data_id
  - DbUpdate( $data_id=NULL ) -- исправляет строку в БД, возвращает $data_id
  - _DbUpdate( &$fields, &$values ) -- формирует sql-запрос и отправляет его в БД
  - _DbAuto( &$fields, &$values ) -- добавляет в $fields, $values автоматически сгенерированные поля

  // Парсинг, иногда можно отдельно пользовать

  * Parse()
  * ParsePreview()

  // Вспомогательные методы

  * StaticDefaults( $default_config, &$supplied_config ) - статичный метод, модифицирует
                                                            supplied_config по дефолтному
                                                            (выставляя все поля, которые
                                                            отсутствуют в супплиеде
  * _ParseWrapper( $content )
  * _ParseButtons()


================================================================== v.0 (kuso@npj)
*/
define( "FORM_EVENT_OK",     "ok");     // ничего не делаем, переход по "success_url", if redirect
define( "FORM_EVENT_CANCEL", "cancel"); // отмена, переход по "cancel_url", if redirect
define( "FORM_EVENT_RESET",  "reset");  // сброс состояния формы к стартовому
define( "FORM_EVENT_INSERT", "insert"); // вставка в БД, переход по "success_url", if redirect
define( "FORM_EVENT_UPDATE", "update"); // правка в БД, переход по "success_url", if redirect
define( "FORM_EVENT_UPDATE_CLIENT", "update_client"); // правка в БД, переход по "success_url", if redirect
define( "FORM_EVENT_DELETE", "delete"); // удалить всё из БД, переход по "success_url", if redirect
define( "FORM_EVENT_AUTO",   "auto");   // insert/update based on $data_id

class Form
{
    var $name; // имя формы
    var $form_present_var = "__form_present";
    var $data_id_var = "__form_data_id";
    var $data_id=0;      // строка, ассоциированная с формой. 0 -- значит нет такой
    var $hash=array();   // очень удобный способ доступа к полям
    var $fields=array(); // очень неудобный способ доступа к полям
    var $buttons=array();// хранилище "кнопок"
    var $action; // куда уходить по посту формы
    
    var $valid = true; // флаг валидности формы
    
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
            "auto_user_id" =>  false,
            "id_field"     =>  "id",
            "active_field" =>  "active",
            "event_handlers_type" => "handlers/formevents", //IVAN
            "default_event" => FORM_EVENT_AUTO,
            "db_ignore" => false,
            "db_table"  => false,
            "fieldname_created_user_id"  => "_created_user_id",
            "fieldname_edited_user_id"   => "_edited_user_id",
            "fieldname_created_datetime" => "_created",
            "fieldname_edited_datetime"  => "_modified",
            // [optional] "success_url" =>
            // [optional] "cancel_url" =>
            // [optional] "on_before_event", "on_after_event"
    );

    public function form($form_config = NULL)
    {

        Finder::UseClass("forms/FormField"); // он нам стопудово понадобится

        if ($form_config['action'])
            $this->action = $form_config['action'];
        else
            $this->action = '';

        if (!$form_config)
            $form_config = $this->default_config;
        else
            Form::StaticDefaults($this->default_config, $form_config);

        if ($form_config['template_form'])
        {
            $parts = explode(":", $form_config['template_form']);
            if (count($parts)==1)
            {
                $form_config['template_form'] = "form.html:".$form_config['template_form'];
            }
        }

        $this->config = $form_config;

        $a = array( "on_before_event", "on_after_event" );
        foreach($a as $v)
        if (isset($form_config[$v]) && !is_array($form_config[$v]))
        {
            $this->config[$v] = array();
            $this->config[$v][] = $form_config[$v];
        }
    }

    // автоматизатор "конфигов по-умолчанию"
    function staticDefaults( $default_config, &$supplied_config )
    {
        foreach( $default_config as $k=>$v )
            if (!isset($supplied_config[$k])) $supplied_config[$k] = $v;
    }

   // Добавить поле
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

   // Добавить кнопку
   function &AddButton( $button_config )
   {
     $this->buttons[$button_config["title"]] = $button_config;
     return $this->buttons[$button_config["title"]];
   }

   // САМАЯ СТРАШНАЯ ПРОЦЕДУРА --------------------------------------------------------
   //zharik: ну, теперь она не такая уж и страшная 8))
   function Handle( $ignore_post     =false,  $ignore_load   =false,
                    $ignore_validator=false,  $ignore_session=false )
   {
     $processed = false;

     //инициализация значений полей
     if ($this->data_id && !$ignore_load) $this->Load();  // пробуем загрузить
     if (!$this->data_id || $ignore_load) $this->Reset(); // устанавливаем default-значения
     if (!$ignore_session) $this->FromSession();

     // присваиваем идетификатор форме
     /*$uid = 0;
     do
     {
        //zharik@gmail.com: $_name should be initilazed before usage
        $_name = $this->config['db_table']? $this->config['db_table'] : 'form';
        if (!$uid) $this->name = $_name;
        else $this->name = $_name.'_'.$uid;
			 $uid++;
     }
     while (isset($this->rh->forms) && in_array($this->name, $this->rh->forms));
     $this->rh->forms[] = $this->name;*/
     $this->name = $this->config['name']? $this->config['name'] : 'form';

     //пробуем обработать пост
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
         // delete
         if ($this->processed && $this->success && $this->deleted && isset($this->config["delete_url"]) )
         {
            Controller::redirect( $this->config["delete_url"] );
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

   //выбираем, какой обработчик события запускать
   function _ChooseEventHandler( $event, $handler, $default_method )
   {
      if (isset($this->config[$handler])){
        foreach($this->config[$handler] as $k=>$v){
          //это может быть отдельная функиця
          //или это может быть объект с явно заданным методом

          if (is_callable($this->config[$handler][$k])){
            call_user_func($this->config[$handler][$k], $event, $this);
          }else
          //это может быть объект с методом по умолчанию
          if ( is_callable( array($this->config[$handler][$k],$default_method) ) ){
            $this->config[$handler][$k]->$default_method( $event, $this );
          }
        }
      }
   }

   // сбросить все поля формы в начальное состояние
   function Reset()
   {
     foreach($this->fields as $field)
       $field->model->Model_SetDefault();
   }

   // парсинг формы в своём обычном состоянии
   function Parse()
   {
     $result = "";
     foreach($this->fields as $field)
       $result .= $field->Parse();
     return $this->_ParseWrapper( $result );
   }

   // парсинг формы "только для чтения", без кнопок
   function ParsePreview()
   {
     $result = "";
     foreach($this->fields as $field)
       $result .= $field->Parse( "readonly" );
     return $result;
   }

   // парсить всякое окружение: кнопки там, прочее
   function _ParseWrapper( $content )
   {
	 $tpl = &Locator::get('tpl');
     $form_name = isset($this->config["form_name"]) ? $this->config["form_name"] : 'form_'.$this->name;
     $tpl->set(
     	"form",
     	"<form action=\"".$this->action."\" ".
     		"method=\"".( $this->config["form_method"] ? $this->config["form_method"] : RequestInfo::METHOD_POST )."\" ".
     		"id=\"".$form_name."\"".
     		"name=\"".$form_name.'" '.
     		($this->config["form_class"] ? 'class="'.$this->config["form_class"].'"' : '' ).
     		($this->config["form_onsubmit"] ? "onsubmit='".$this->config["form_onsubmit"]."'" : '' ).
     		' enctype="multipart/form-data"> '. RequestInfo::pack(METHOD_POST)
     );

     $tpl->set( "form_name", 'form_'.$this->name );
     $tpl->set( "form_present", $this->form_present_var );
     $tpl->set( "form_data_id", $this->data_id_var );
     $tpl->set( "form_data_id_value", $this->data_id );
     $tpl->set( "content", $content );
     $tpl->set( "data_id", $this->data_id );
     $tpl->set( "buttons", $this->_ParseButtons() );

     //отпарсить кнопки по отдельности
     $tpl->set( "buttons_left",  $this->_ParseButtons(0) );
     $tpl->set( "buttons_right", $this->_ParseButtons(1) );

     return $tpl->Parse( $this->config["template_prefix"].$this->config["template_form"]);

   }

   // парсинг кнопок
   function _ParseButtons($index=null)
   {
     $tpl = &TemplateEngine::getInstance();
     $result = array();
     foreach( $this->buttons as $button )
     {
       $tpl->SetRef( "*", $button );

       //отпарсить специальную кнопку в нужное место
       if (!empty($button['store_to']))
       {
         $tpl->Parse( $this->config["template_prefix_button"].$button["template"], $button['store_to'] );
        }
       else
           $result[]["BUTTON"] = $tpl->Parse( $this->config["template_prefix_button"].$button["template"] );
     }

     //отпарсить только одну кнопку
     if ($index!==null)
     {
       $tpl->setRef('*', $result[$index]);
       $ret = $tpl->parse($this->config["template_prefix"].$this->config["template_buttonlist"]."_Item");

       return  $ret;
     }
     else
       return $tpl->set('buttons', $result, $this->config["template_prefix"].$this->config["template_buttonlist"] );
   }

   // загрузка из формы
   function LoadFromPost( $post_data )
   {
     $this->AssignId( @$post_data[ $this->data_id_var ] ); //IVAN

     foreach($this->fields as $k=>$field)
     {
       $this->fields[$k]->LoadFromPost( $post_data );
     }
   }

   // валидация всех полей формы
   function Validate()
   {
     $this->valid = true;
     foreach($this->fields as $k=>$field)
       $this->valid = $this->fields[$k]->Validate() && $this->valid; // важно, что именно в таком порядке
     return $this->valid;
   }

   // ДАМП ФОРМЫ
   function _Dump( $is_error=1 )
   {
     $dump_hash = array();
     foreach( $this->fields as $k=>$field )
      $dump_hash[ $field->name ] = $field->_Dump();
   }

   // работа в сессии
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

   // обработка события, ага (вставка/редактирование)
   function HandleEvent( $event = FORM_EVENT_AUTO )
   {
     if (is_array($event)) $_event = $event["event"];
     else                  $_event = $event;

     if ($_event == FORM_EVENT_AUTO)
     {
       if ($this->data_id) $_event = FORM_EVENT_UPDATE;
       else                $_event = FORM_EVENT_INSERT;
     }

     $this->deleted = false;
     switch( $_event )
     {
       case FORM_EVENT_INSERT:
                              $this->dbInsert();
                              $this->success   = true;
                              $this->processed = true;
                              break;
       case FORM_EVENT_UPDATE:
                              $this->dbUpdate();
                              $this->success   = true;
                              $this->processed = true;
                              break;
       case FORM_EVENT_UPDATE_CLIENT:
                              $this->DbUpdate();
                              $this->success   = true;
                              $this->processed = true;
                              break;
       case FORM_EVENT_DELETE:
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
		$this->ResetSession(); // если успешно обработана, то сессию выкидываем
   }

    // вставка в БД
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
        foreach($this->fields as $k=>$v)
            $this->fields[$k]->dbUpdate( $dataId, $fields, $values );
            
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
            $this->fields[$k]->DbAfterUpdate( $data_id );
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

    // загрузка из БД
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
            if (is_string($this->config["db_model"]))
                $model = DBModel::factory($this->config["db_model"]);
            else
                $model = $this->config["db_model"];
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

   // удаление из БД
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

   // загрузка из массива
   function LoadFromArray( $data )
   {
     foreach($this->fields as $k=>$v)
       $this->fields[$k]->LoadFromArray( $data );
   }

   // ставим форме в соответствие строку в БД
   function AssignId( $data_id )
   {
     $this->data_id = $data_id;
   }
   
    public function &getFieldByName($name)
    {
        foreach ($this->fields as $k => $field)
        {
            if ($field->name == $name) return $this->fields[$k];
        }
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
