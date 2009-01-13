<?php
/*

  Форм-процессор
  * see http://in.jetstyle.ru/rocket/rocketforms

  FormComponent_date( &$config )
      - $field -- $field->config instance-a поля  

  -------------------

  * model       : работа с двумя полями "field" & "field_active", первое -- datetime, второе -- int
  * interface   : вывод поля с чекбоксом и календариком
  * validator   : пришла ли нам дата в правильном формате?
  * view        : наследуем из него, чтобы выводить readonly-значение

  -------------------

  Опции в конфиге

  * view_date_format      = "d.m.Y" 
  * interface_date_format = "d.m.Y" 

  * date_optional = true|false (false is default)

  -------------------

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

  // Интерфейс (парсинг и обработка данных)
  * Interface_Parse()
  * Interface_PostToArray( $post_data )

  // Парсинг (оформление и отображение)
  * View_Parse()

================================================================== v.0 (kuso@npj)
*/
Finder::UseClass( "forms/components/view_plain" );

class FormComponent_date extends FormComponent_view_plain
{
   var $zero_data = "0000-00-00 00:00:00";

   // MODEL ==============================================================================
   // сброс значения в "значение по-умолчанию"
   function Model_SetDefault()
   {
     $this->model_data        = isset($this->field->config["model_default"]) ? $this->field->config["model_default"] : "";
     $this->model_data_active = isset($this->field->config["model_default_active"]) ? $this->field->config["model_default_active"] : "";
   }
   // возврат значения в виде "шифра" или "ключа"
   function Model_GetDataValue()
   {
     return $this->model_data_active."|".$this->model_data;
   }
   // изменение значения в виде "шифра" или "ключа"
   function Model_SetDataValue($model_value)
   { 
     $a = explode( "|", $model_value );
     $this->model_data_active = $a[0];
     $this->model_data        = $a[1];
   }
   // dbg purposes: dump
   function Model_Dump()
   {
     return $this->Model_GetDataValue();
   }
   // ---- сессия ----
   function Model_ToSession( &$session_storage )
   {
     $session_storage[ $this->field->name ] = $this->model_data;
     $session_storage[ $this->field->name."_active" ] = $this->model_data_active;
   }
   function Model_FromSession( &$session_storage )
   {
     $this->model_data        = $session_storage[ $this->field->name ];
     $this->model_data_active = $session_storage[ $this->field->name."_active" ];
   }
   // ---- работа с хранилищем в массиве ----
   function Model_LoadFromArray( $a )
   {
     $this->model_data        = $a[ $this->field->name ];
     $this->model_data_active = $a[ $this->field->name."_active" ];
   }
   function Model_ToArray( &$a )
   {
     $a[ $this->field->name           ] = $this->model_data;
     $a[ $this->field->name."_active" ] = $this->model_data_active;
   }
   // ---- работа с БД ----
   function Model_DbLoad( $db_row )
   { 
     if(isset($db_row[ $this->field->name ]))
     {
       $this->model_data        = $db_row[ $this->field->name ];
       $this->model_data_active = $db_row[ $this->field->name."_active" ];
     }
     else
      $this->Model_SetDefault();
   }
   function Model_DbInsert( &$fields, &$values )
   {
     $fields[] = $this->field->name;
     $values[] = $this->model_data;
     if ($this->field->config["date_optional"])
     {
       $fields[] = $this->field->name."_active";
       $values[] = $this->model_data_active;
     }
   }
   function Model_DbUpdate( $data_id, &$fields, &$values )
   {
     return $this->Model_DbInsert( $fields, $values );
   }
  
   // VALIDATOR ==============================================================================
   function Validate()
   {
     FormComponent_abstract::Validate();

     // @todo: валидатор даты
     // $this->_Invalidate( "date_wrong", "Неверный формат!" );

     return $this->valid;
   }
  
   // VIEW ==============================================================================
   // парсинг readonly значения
   function View_Parse( $plain_data=NULL )
   {
     if ($plain_data == NULL)
     {
       $plain_data = "&mdash;";
       if ($this->model_data_active)
         if (isset( $this->field->config["view_date_format"] ) )
           $plain_data = date( $this->field->config["view_date_format"], strtotime( $this->model_data ) );
         else
           $plain_data = $this->field->rh->msg->ConvertDate( $this->model_data );
     }

     return parent::View_Parse( $plain_data );
   }

   // INTERFACE ==============================================================================
   // парсинг полей интерфейса
   function Interface_Parse()
   { 
     parent::Interface_Parse();

     $format = "d.m.Y";
     if (isset($this->field->config["view_interface_format"])) $format = $this->field->config["view_interface_format"];

     if ($this->model_data == $this->zero_data)
     {
       $date = "";
       $chk  = 0;
     }
     else
     {
       $date = date( $format, strtotime($this->model_data));
       $chk  = $this->model_data_active;
     }

     $this->field->tpl->Set( "interface_date", $date );
     $this->field->tpl->Set( "interface_checkbox", $chk);

     return $this->field->tpl->Parse($this->field->form->config["template_prefix_interface"].
                                     $this->field->config["interface_tpl"]);
   }
   // преобразование из поста в массив для загрузки моделью
   function Interface_PostToArray( $post_data )
   {
     $a = array( 
          $this->field->name           => @trim($post_data['_'.$this->field->name]),
          $this->field->name."_active" => ($post_data['_'.$this->field->name."_active"]?1:0),
                  );
     if (preg_match( "/([0-9]{1,2})\.([0-9]{1,2})\.([0-9]{4})/", $a[$this->field->name], $m ))
       $a[$this->field->name] = date("Y-m-d 00:00:00", mktime(0,0,0,ltrim($m[2],"0"),ltrim($m[1],"0"),$m[3]));
     else
       $a[$this->field->name] = $this->zero_data;

     return $a; 
   }

}

?>
