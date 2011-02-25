<?php
/*

  Форм-процессор
  * see http://in.jetstyle.ru/rocket/rocketforms

  Вспомогательный класс.

  FormField( &$form, $field, &$config )
      - $field           -- уникальный идентификатор поля, вида: "subject", "body_r", "author_id"
      - &$config         -- ссылка на конфиг поля. Обязательно ссылка, потому что может попастся
                            массив из полей

  -------------------

  // внутренние
  * _BuildComponents() -- строит все нужные компоненты и привязывает их в $this
  * _LinkToForm( &$form ) -- привязка к форме
  * _Dump() -- используется для вывода "содержимого" формы при отладке

  // Общение с формой
  * Parse()                    -- парсинг поля для формы
  * LoadFromPost( $post_data ) -- распознавание своих данных из поста
  * Validate()                 -- валидация текущего поля
  * ToSession/FromSession      -- работа с сессией
  * Db<*> -- набор прослоек для работы с БД

================================================================== v.0 (kuso@npj)
*/

class FormField {
    var $name=""; // идентификатор поля
    var $default_config = array(
        
    );

    public function __construct( &$form, $field=null, &$config ) {
        $this->form = &$form;

        if ($field == NULL)
        {
            $field = $form->_NextInnerName();
        }
        $this->name = $field;

        if (!is_array($config))
        {
            $config = $this->default_config;
        }
        else
        {
            Form::StaticDefaults($this->default_config, $config);
        }
        $this->config = $config;
    }

    // привязка к форме
    function _LinkToForm( &$form ) {
        $this->form = &$form;
        $this->Event_Register();
    }
    
    function Event_Register()
    {
        Debug::Trace( "event_register for: { ".$this->field->name." } ");
        $this->form->hash[ $this->name ] = &$this->field;
    }

    public function &getFieldByName($name) {
        $resultField = null;
        
        if ($this->model && method_exists($this->model, 'getFieldByName'))
        {
            $resultField = $this->getFieldByName($name);
        }
        
        return $resultField;
    }

    // парсинг поля формы
    function Parse( $is_readonly=false ) {
        Debug::trace("FormField: <b>Parsing field: { ".$this->name." } </b>");

        if ($is_readonly || $this->config["readonly"] || $this->form->config["readonly"])
        {
            $result = $this->View_Parse();
        }
        else
        {
            if (isset($this->config["view_wrap_interface"]) && $this->config["view_wrap_interface"])
            {
                $result = $this->View_Parse( $this->Interface_Parse() );
            }
            else
            {
                $result = $this->Interface_Parse();
            }
        }

        Debug::trace("FormField: interface parsed");

        $ret = $this->Wrapper_Parse( $result );

        Debug::trace("FormField: wrapper: ".get_class($this->wrapper));

        return $ret;
    }

    // распознавание данных из поста
    function LoadFromPost( $post_data )
    {
        if ($this->config["readonly"]) {
            return;
        }
        return $this->Model_LoadFromArray(
            $this->Interface_PostToArray( $post_data )
        );
    }
    
    function LoadFromArray( $a )
    {
        if (@$this->config["readonly"]) return;
        return $this->Model_LoadFromArray( $a );
    }

    // сессия
    function ToSession( &$session_storage )
    {
        if (@$this->config["readonly"]) return;
        $this->Model_ToSession( $session_storage );
    }
    
    function FromSession( &$session_storage )
    {
        if (@$this->config["readonly"]) return;
        $this->Model_FromSession( $session_storage );
    }

    // для отладок
    function _Dump()
    {
        return $this->Model_Dump();
    }

    // сохранение в БД
    function DbInsert( &$fields, &$values )
    {
        if (@!$this->config["db_ignore"])
            return $this->Model_DbInsert( $fields, $values );
    }
    
    function DbAfterInsert( $data_id )
    {
        if (@!$this->config["db_ignore"])
            return $this->Model_DbAfterInsert( $data_id );
    }
    
    function DbUpdate( $data_id, &$fields, &$values )
    {
        if (@!$this->config["db_ignore"])
            return $this->Model_DbUpdate( $data_id, $fields, $values );
    }
    
    function DbAfterUpdate( $data_id )
    {
        if (@!$this->config["db_ignore"])
            return $this->Model_DbAfterUpdate( $data_id );
    }
    
    function Model_DbAfterInsert($data_id)
    {
    }
    
    function Model_DbAfterUpdate($data_id)
    {
    }
    
    function DbLoad( $data_id )
    {
        if (@!$this->config["db_ignore"])
            return $this->Model_DbLoad( $data_id );
        else
        {
            $this->Model_SetDefault();
            return $this->Model_GetDataValue();
        }
    }
    
    function DbDelete( $data_id ) {
        if (@!$this->config["db_ignore"])
            return $this->Model_DbDelete( $data_id );
    }

    //abstract
    function Validate()
    {
        $this->valid = true;
        $this->validator_params = $this->config["validator_params"];
        $this->validator_messages = array();
        
        if (!empty($this->config['validator_params']))
        {
            Finder::useClass('Validator');
            foreach($this->config['validator_params'] as $key => $param)
            {
                if (is_numeric($key))
                {
                    $isValid = Validator::testValue($this->Model_GetDataValue(), $param, true);
                    $method = $param;
                }
                else
                {
                    $isValid = Validator::testValue($this->Model_GetDataValue(), $key, $param);
                    $method = $key;
                }
                if (!$isValid)
                {
                    $this->_Invalidate( $method, Locator::get('msg')->get('validator_error_'.$method) );
                    break;
                }
            }   
        }
        
        return $this->valid;
    }

    // этот метод нужно звать, чтобы инвалидировать поле с этим валидатором.
    // $reason -- ключ для мессаджсета,
    function _Invalidate( $reason, $msg="there is no custom message", $show_general_form_error=true )
    {

        $this->valid = false;

        $value = $msg;//$this->field->rh->tpl->msg->Get( 'Form:Validator/'.$reason );
        if (!empty($value) && $value != 'Form:Validator/'.$reason)
        {
            $msg = $value;    
        }
        Locator::get('tpl')->set('show_general_form_error', $show_general_form_error);
        $this->validator_messages[$reason] = $msg;
    }
   
    //model
    function Model_SetDefault()
    {
        $this->model_data = isset($this->config["model_default"]) ? $this->config["model_default"] : "";
    }
    
    function Model_GetDataValue()
    {
        return $this->model_data;
    }

    function Model_SetDataValue($model_value)
    { 
        $this->model_data = $model_value;
    }


    function Model_Dump()
    {
        return $this->model_data;
    }

    function Model_ToSession( &$session_storage )
    {
        $session_storage[ $this->name ] = $this->model_data;
    }
    
    function Model_FromSession( &$session_storage )
    {
      $this->model_data = $session_storage[ $this->name ];
    }
    
    function Model_LoadFromArray( $a )
    {
        $this->model_data = $a[ $this->name ];
        // получаем из другого поля в конфиге (должно быть "выше" по форме)
        if (isset($this->config["model_empty_from"]) && $this->model_data == "")
        {
            $this->model_data = $this->form->hash[$this->config["model_empty_from"]]->Model_GetDataValue();
        }
    }
    
    function Model_ToArray( &$a )
    {
        $a[ $this->name ] = $this->model_data;
    }
    
    function Model_DbLoad( $db_row )
    {
        if(isset($db_row[ $this->name ]))
        {
            $this->model_data = $db_row[ $this->name ];
        }
        else
        {
            $this->Model_SetDefault();
        }
    }
    
    function Model_DbInsert( &$fields, &$values )
    {
        $fields[] = $this->name;
        $values[] = $this->model_data;
    }
    
    function Model_DbUpdate( $data_id, &$fields, &$values )
    {
        return $this->Model_DbInsert( $fields, $values );
    }
   
   //wrapper
    function Wrapper_Parse( $field_content )
    {
        // если есть ошибки?
        $tpl = Locator::get('tpl');
        $tpl->set( "errors", "" );
        $tpl->set( "is_valid", $this->valid );
        if (!$this->valid)
        {
            $msgs = array();
            if (is_array($this->validator_messages))
            {
                foreach( $this->validator_messages as $msg=>$text ) {
                    $msgs[] = array( "msg" => $msg, "text" => $text );
                }
                $tpl->set('msgs', $msgs);
                $tpl->parse($this->form->config["template_prefix"]."errors.html:List",'errors');
            }
            else
            {
                $tpl->Set( "errors", "" );
            }
        }
   
        // парсим обёртку
        $tpl->set( "field", "_".$this->field->name ); // на всякий случай
   
        $tpl->set(
            "not_empty",
            isset($this->config["validator_params"]["not_empty"]) && $this->config["validator_params"]["not_empty"] ? 1 : 0
        );
   
        $tpl->set( "content",        $field_content  );
        $tpl->set( "wrapper_title",  isset($this->config["wrapper_title"]) && $this->config["wrapper_title"] ? $this->config["wrapper_title"] : "" );
        $tpl->set( "wrapper_desc",   isset($this->config["wrapper_desc"]) && $this->config["wrapper_desc"] ? $this->config["wrapper_desc"] : "" );
   
        return $tpl->parse(
            (isset($this->form->config["template_prefix_wrappers"]) ? $this->form->config["template_prefix_wrappers"] : "" ).
            (isset($this->config["wrapper_tpl"]) ? $this->config["wrapper_tpl"] : "" )
        );
    }
   
    //view
    function View_Parse( $plain_data=NULL )
    {
        if ($plain_data !== NULL)
        {
            $data = $plain_data;
        }
        else
        {
            $data = $this->Model_GetDataValue();
        }
       
        $this->Interface_Parse(); // parse to get use of "interface_tpl_params"

        if (isset($this->field->config["view_tpl"])) 
        {
            $this->field->tpl->Set( "view_prefix",  isset($this->field->config["view_prefix"]) ? $this->field->config["view_prefix"] : "" );
            $this->field->tpl->Set( "view_postfix", isset($this->field->config["view_postfix"]) ? $this->field->config["view_postfix"] : "" );
            $this->field->tpl->Set( "view_data",    $data );
     
            $data = $this->field->tpl->Parse( $this->field->form->config["template_prefix_views"].$this->field->config["view_tpl"] );
        }
        else // вариант для бедных
        {
            if (isset($this->field->config["view_prefix"]))
            {
                $data= $this->field->config["view_prefix"].$data;
            }
            if (isset($this->field->config["view_postfix"]))
            {
                $data= $this->field->config["view_postfix"].$data;
            }
        }
        return $data;
    }
   
    //interface
    function Interface_SafeDataValue( $data_value )
    {
        return htmlspecialchars($data_value);
    }
    
    // парсинг полей интерфейса
    function Interface_Parse()
    {
        if (!$this->config["interface_tpl"]) return false;
        
        $_data = $this->Model_GetDataValue();
        $data  = $this->Interface_SafeDataValue($_data);
   
        Locator::get('tpl')->set( "interface_data", $data );
   
        $tpl = Locator::get('tpl');
        $tpl->set( "field", "_".$this->name );
        $tpl->set( "field_id", "id_".$this->form->name."_".$this->name );
        if (isset($this->config["interface_tpl_params"]) && is_array($this->config["interface_tpl_params"]))
        {
            foreach( $this->config["interface_tpl_params"] as $param=>$value )
            {
                $tpl->set("params_".$param, $value );
            }
        }
        $result = "";

        return Locator::get('tpl')->parse( $this->form->config["template_prefix_interface"].
                                         $this->config["interface_tpl"] );
    }
    
    // преобразование из поста в массив для загрузки моделью
    function Interface_PostToArray( $post_data )
    {
        return array(
            $this->name => rtrim($post_data["_".$this->name]),
        );
    }
}


?>
