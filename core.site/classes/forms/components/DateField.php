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
Finder::UseClass( "forms/FormField" );

class DateField extends FormField
{
   var $zero_data = "0000-00-00 00:00:00";

   // MODEL ==============================================================================
   // сброс значения в "значение по-умолчанию"
   function Model_SetDefault()
   {
     $this->model_data        = isset($this->config["model_default"]) ? $this->config["model_default"] : "";
     $this->model_data_active = isset($this->config["model_default_active"]) ? $this->config["model_default_active"] : "";
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
     $session_storage[ $this->name ] = $this->model_data;
     $session_storage[ $this->name."_active" ] = $this->model_data_active;
   }
   function Model_FromSession( &$session_storage )
   {
     $this->model_data        = $session_storage[ $this->name ];
     $this->model_data_active = $session_storage[ $this->name."_active" ];
   }
   // ---- работа с хранилищем в массиве ----
   function Model_LoadFromArray( $a )
   {
     $this->model_data        = $a[ $this->name ];
     $this->model_data_active = $a[ $this->name."_active" ];
   }
   function Model_ToArray( &$a )
   {
     $a[ $this->name           ] = $this->model_data;
     $a[ $this->name."_active" ] = $this->model_data_active;
   }
   // ---- работа с БД ----
   function Model_DbLoad( $db_row )
   {
     if(isset($db_row[ $this->name ]))
     {
       $this->model_data        = $db_row[ $this->name ];
       $this->model_data_active = $db_row[ $this->name."_active" ];
     }
     else
      $this->Model_SetDefault();
   }
   function Model_DbInsert( &$fields, &$values )
   {
     $fields[] = $this->name;
     $values[] = $this->model_data;
     if ($this->config["date_optional"])
     {
       $fields[] = $this->name."_active";
       $values[] = $this->model_data_active;
     }
     $parts = explode(" ", $this->model_data);
     $dates = explode("-", $parts[0]);

     if (@in_array("year", $this->config["update_fields"] )){
         $fields[] = "year";
         $values[] = $dates[0];
     }
     if (@in_array("month", $this->config["update_fields"] )){
         $fields[] = "month";
         $values[] = $dates[1];
     }
     if (@in_array("day", $this->config["update_fields"] )){
         $fields[] = "day";
         $values[] = $dates[2];
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
         //if (isset( $this->field->config["view_date_format"] ) )
           $plain_data = date( $this->config["view_date_format"], strtotime( $this->model_data ) );
         //else
         //  $plain_data = $this->field->rh->msg->ConvertDate( $this->model_data );
     }
    

     return parent::View_Parse( $plain_data );
   }

   // INTERFACE ==============================================================================
   // парсинг полей интерфейса
   function Interface_Parse()
   {
     parent::Interface_Parse();

     $format = "d.m.Y";
     $format_time = "H:i";
     if (isset($this->config["view_interface_format"])) $format = $this->config["view_interface_format"];

     if ($this->model_data == $this->zero_data || $this->model_data=="")
     {
     
       $date = date($format);//"";
       $time = $this->config["use_time"] ? date($format_time) : "00:00";
       $chk  = 0;
       
     }
     else
     {
       $date = date( $format, strtotime($this->model_data));
       $time = date( $format_time, strtotime($this->model_data));
       $chk  = $this->model_data_active;
     }
     
     //var_dump($this->model_data, $date);

     Locator::get("tpl")->Set( "interface_data", $date );
     Locator::get("tpl")->Set( "interface_checkbox", $chk);
     if ( $this->config["use_time"] )
         Locator::get("tpl")->Set( "interface_time", $time );

     $ret = Locator::get("tpl")->Parse($this->form->config["template_prefix_interface"].
                                     $this->config["interface_tpl"]);
     return $ret;
   }
   // преобразование из поста в массив для загрузки моделью
   function Interface_PostToArray( $post_data )
   {
     $a = array(
          $this->name           => @trim($post_data['_'.$this->name]),
          $this->name."_active" => ($post_data['_'.$this->name."_active"]?1:0),
          $this->name."_time" => ($post_data['_'.$this->name."_time"]? $post_data['_'.$this->name."_time"]:0)
                  );
     if (preg_match( "/([0-9]{1,2})\.([0-9]{1,2})\.([0-9]{4})/", $a[$this->name], $m ))
     {
       if ( $a[$this->name."_time"] ){
          $parts = explode(":", $a[$this->name."_time"]);
          $hour = $parts[0];
          $minute = $parts[1];
          if ($hour > 23)
            $hour = 23;
          else if ($hour < 0 )
            $hour = 0;

          if ($minute < 0 )
            $minute = 0;
          else if ($minute >59 )
            $minute = 59;
          
       }
       else
       {
           
            $hour = $minute = 0;
       }
       $a[$this->name] = date("Y-m-d ".$hour.":".$minute.":00", mktime($hour,$minute,0,ltrim($m[2],"0"),ltrim($m[1],"0"),$m[3]));
       
     }
     else
       $a[$this->name] = $this->zero_data;

     return $a;
   }

}

?>
