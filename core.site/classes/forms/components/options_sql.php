<?php
/*

  Форм-процессор
  * see http://in.jetstyle.ru/rocket/rocketforms

  FormComponent_options_sql( &$config )
      - $field -- $field->config instance-a поля

  -------------------

  * interface   : ПРОСТОЙ ВЫБОР. Выбор из списка через select или radio.
  * view        : ПРОСТОЙ ВЫБОР. Выводит значение для ключа (оформляя его в шаблон) из БД по запросу
                  field->config["options_sql"] = "select ... as id, ... as name from table"

  -------------------

  // Интерфейс (парсинг и обработка данных)

  * Interface_Parse()
  * Interface_PostToArray( $post_data )
  * View_Parse( $plain_data=NULL )

================================================================== v.0 (kuso@npj)
*/

Finder::UseClass("forms/components/options");

class FormComponent_options_sql extends FormComponent_options
{
   // получим список опций из БД
   function _PrepareOptions()
   {
     $options = Locator::get('db')->query( $this->field->config["options_sql"] );
     $data = array();
     foreach( $options as $k=>$v ) $data[ $v["id"] ] = $v["name"];
     $this->field->config["options"] = isset($this->field->config["options"]) ? $this->field->config["options"] + $data : $data;
   }

   // INTERFACE ==============================================================================
   // парсинг полей интерфейса
   function Interface_Parse()
   {
     $this->_PrepareOptions();
     return parent::Interface_Parse();
   }
   // преобразование из поста в массив для загрузки моделью
   function Interface_PostToArray( $post_data )
   {
     $this->_PrepareOptions();
     return parent::Interface_PostToArray( $post_data );
   }

   // VIEW ==============================================================================
   // парсинг readonly значения
   function View_Parse( $plain_data=NULL )
   {
     $this->_PrepareOptions();
     return parent::View_Parse( $plain_data );
   }


// EOC{ FormComponent_options_sql }
}


?>
