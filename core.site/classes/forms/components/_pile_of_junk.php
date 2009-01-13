<?php
/*

  Форм-процессор
  * see http://in.jetstyle.ru/rocket/rocketforms

  Один компонент, пытающийся вобрать всё.

  FormComponent__pile_of_junk( &$config )
      - $field -- $field->config instance-a поля  

  -------------------

  Вся номенклатура в одном флаконе:

  // Модель. Операции с данными и хранилищем

  * Model_LoadFromArray( $a )
  * Model_ToArray( &$a ) 

  * Model_DbInsert( &$fields, &$values )
  * Model_DbUpdate( $data_id, &$fields, &$values )
  * Model_DbAfterInsert( $data_id )
  * Model_DbAfterUpdate( $data_id )
  * Model_DbDelete( $data_id )
  * Model_DbLoad( $db_row )

  * Model_SetDefault()

  * Model_ToSession( &$session_storage )
  * Model_FromSession( &$session_storage )

  * Model_GetDataValue()

  // Валидатор

  * Validate()

  // Парсинг (оформление и отображение)

  * Wrapper_Parse( $field_content )
  * View_Parse()

  // Интерфейс (парсинг и обработка данных)
  * Interface_SafeDataValue( $data_value )
  * Interface_Parse()
  * Interface_PostToArray( $post_data )

  // Настройка и подстройка
  * Event_Register() -- регистрация в форме. Перегружать имеет смысл только для групп
  * LinkToField( &$field ) -- привязаться к полю. 
                              Делается отдельно, чтобы дать возможность создавать компоненты вручную

================================================================== v.0 (kuso@npj)
*/

class FormComponent__pile_of_junk extends FormComponent_abstract
{
   // MODEL ==============================================================================
   // сброс значения в "значение по-умолчанию"
   function Model_SetDefault()
   {
     $this->model_data = $this->field->config["model_default"];
   }
   // возврат значения в виде "шифра" или "ключа"
   function Model_GetDataValue()
   {
     return $this->model_data;
   }
   // dbg purposes: dump
   function Model_Dump()
   {
     return $this->model_data;
   }
   // ---- сессия ----
   function Model_ToSession( &$session_storage )
   {
     $session_storage[ $this->field->name ] = $this->model_data;
   }
   function Model_FromSession( &$session_storage )
   {
     $this->model_data = $session_storage[ $this->field->name ];
   }
   // ---- работа с хранилищем в массиве ----
   function Model_LoadFromArray( $a )
   {
     $this->model_data = $a[ $this->field->name ];
   }
   function Model_ToArray( &$a )
   {
     $a[ $this->field->name ] = $this->model_data;
   }
   // ---- работа с БД ----
   function Model_DbLoad( $db_row )
   { 
     $this->model_data = $db_row[ $this->field->name ];
   }
   function Model_DbInsert( &$fields, &$values )
   {
     $fields[] = $this->field->name;
     $values[] = $this->model_data;
   }
   function Model_DbUpdate( $data_id, &$fields, &$values )
   {
     return $this->Model_DbInsert( &$fields, &$values );
   }
   function Model_DbAfterInsert( $data_id )
   { /* abstract */ }
   function Model_DbAfterUpdate( $data_id )
   { /* abstract */ }
   function Model_DbDelete( $data_id )
   { /* abstract */ }


   // VALIDATOR ==============================================================================
   // валидация
   // все потомки должны вызывать его ПЕРЕД своей валидацией
   function Validate()
   {
     $this->valid = true;
     $this->validator_messages = array();
     // ПРИМЕР: что делать, если невалидно?
     // ПРИМЕР: $this->_Invalidate( "empty", "Поле обязательно для заполнения" );
     return $this->valid;
   }
   // этот метод нужно звать, чтобы инвалидировать поле с этим валидатором.
   // $reason -- ключ для мессаджсета, 
   function _Invalidate( $reason, $msg="there is no custom message" )
   {
     $this->valid=false;
     $this->validator_messages[$reason] = $msg;
   }


   // WRAPPER ===========================================================================
   // оформление вокруг поля
   function Wrapper_Parse( $field_content )
   {
     // если есть ошибки?
     $this->field->tpl->Set( "is_valid", $this->field->validator->valid );
     if (!$this->field->validator->valid)
     {
       $msgs = array();
       foreach( $this->field->validator->validator_messages as $msg=>$text )
        $msgs[] = array( "msg" => $msg, "text" => $text );
       $this->field->tpl->Loop( $msgs, $this->field->form->config["template_prefix"]."errors.html:List", "errors" );
     }

     // парсим обёртку
     $this->field->tpl->Set( "content",        $field_content  );
     $this->field->tpl->Set( "wrapper_title",  $this->field->config["wrapper_title"] );
     $this->field->tpl->Set( "wrapper_desc",   $this->field->config["wrapper_desc"]  );
     return $this->field->tpl->Parse( $this->field->form->config["template_prefix_wrappers"].
                                      $this->field->config["wrapper_tpl"] );
   }

   // VIEW ==============================================================================
   // парсинг readonly значения
   function View_Parse()
   {
     $data = $this->field->model->Model_GetDataValue();
     if ($this->field->config["view_tpl"])
     {
       $this->field->tpl->Set( "view_prefix",  $this->field->config["view_prefix"] );
       $this->field->tpl->Set( "view_postfix", $this->field->config["view_postfix"] );
       $this->field->tpl->Set( "view_data",    $data );
       $data = $this->field->tpl->Parse( $this->form->config["template_prefix_views"].
                                         $this->field->config["view_tpl"] );
     }
     else // вариант для бедных
     {
       if ($this->field->config["view_prefix"])
         $data= $this->field->config["view_prefix"].$data;
       if ($this->field->config["view_postfix"])
         $data= $this->field->config["view_postfix"].$data;
     }
     return $data;
   }

   // INTERFACE ==============================================================================
   // защита поля от "клиента"
   function Interface_SafeDataValue( $data_value )
   {
     return htmlspecialchars($data_value);
   }
   // парсинг полей интерфейса
   function Interface_Parse()
   {
     $_data = $this->field->model->Model_GetDataValue();
     $data  = $this->field->interface->Interface_SafeDataValue($_data);

     $this->field->tpl->Set( "interface_data", $data );
     $this->field->tpl->Set( "field",          "_".$this->field->name );
     return $this->field->tpl->Parse( $this->field->form->config["template_prefix_interface"].
                                      $this->field->config["interface_tpl"] );
   }
   // преобразование из поста в массив для загрузки моделью
   function Interface_PostToArray( $post_data )
   {
      return array(
                $this->field->name => rtrim($post_data["_".$this->field->name]),
                   );
   }

// EOC{ FormComponent__pile_of_junk }
}  
   

?>