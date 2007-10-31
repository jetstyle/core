<?php
/*

  Форм-процессор
  * see http://in.jetstyle.ru/rocket/rocketforms

  Один компонент, максимально абстрактный.

  FormComponent_abstract( &$config )
      - $field -- $field->config instance-a поля  

  -------------------

  * model       : абстрактная. Ничего не делает
  * validator   : абстрактный. Всегда валидный, все сервисные методы есть
  * wrapper     : абстрактный. Всегда возвращает пустоту
  * view        : абстрактный. Всегда возвращает пустоту
  * interface   : абстрактный. Не выводит шаблона, игнорирует данные из поста

  -------------------

  Вся номенклатура в одном флаконе -- на память:

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

class FormComponent_abstract
{
   var $default_config = array();
   var $model_data;
   var $valid = true;

   function FormComponent_abstract( &$config )
   {
     if (sizeof($this->default_config) > 0) 
       Form::StaticDefaults($this->default_config, $config);
   }

   // привязка к полю
   function LinkToField( &$field )
   {
     $this->field = &$field;
   }

   // регистрация в форме
   function Event_Register()
   {
     $this->field->rh->debug->Trace( "event_register for: { ".$this->field->name." } ");
     $this->field->form->hash[ $this->field->name ] = &$this->field;
   }

   // MODEL ==============================================================================
   // сброс значения в "значение по-умолчанию"
   function Model_SetDefault()
   { /* abstract */ }
   // возврат значения в виде "шифра" или "ключа"
   function Model_GetDataValue()
   { /* abstract */ }
   // dbg purposes: dump
   function Model_Dump()
   { /* abstract */ }
   // ---- сессия ----
   function Model_ToSession( &$session_storage )
   { /* abstract */ }
   function Model_FromSession( &$session_storage )
   { /* abstract */ }
   // ---- работа с хранилищем в массиве ----
   function Model_LoadFromArray( $a )
   { /* abstract */ }
   function Model_ToArray( &$a )
   { /* abstract */ }
   // ---- работа с БД ----
   function Model_DbLoad( $db_row )
   { /* abstract */ }
   function Model_DbInsert( &$fields, &$values )
   { /* abstract */ }
   function Model_DbUpdate( $data_id, &$fields, &$values )
   { /* abstract */ }
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
     $this->validator_params = $this->field->config["validator_params"];
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
   { return ""; }

   // VIEW ==============================================================================
   // парсинг readonly значения
   function View_Parse()
   { return ""; }

   // INTERFACE ==============================================================================
   // защита поля от "клиента"
   function Interface_SafeDataValue( $data_value )
   { return ""; }
   // парсинг полей интерфейса
   function Interface_Parse()
   { 
     $this->field->tpl->Set( "field", "_".$this->field->name );
     if (isset($this->field->config["interface_tpl_params"]) && is_array($this->field->config["interface_tpl_params"]))
     {
       foreach( $this->field->config["interface_tpl_params"] as $param=>$value )
         $this->field->tpl->Set("params_".$param, $value );
     }
     return "";
   }
   // преобразование из поста в массив для загрузки моделью
   function Interface_PostToArray( $post_data )
   { return array(); }

// EOC{ FormComponent_abstract }
}  
   

?>