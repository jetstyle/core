<?php
/*

  Форм-процессор
  * see http://in.jetstyle.ru/rocket/rocketforms

  FormComponent_interface_string( &$config )
      - $field -- $field->config instance-a поля

  -------------------

  * interface   : СТРОКА. типовой интерфейс строки

  -------------------

  // Интерфейс (парсинг и обработка данных)

  * Interface_SafeDataValue( $data_value )
  * Interface_Parse()
  * Interface_PostToArray( $post_data )

================================================================== v.0 (kuso@npj)
*/

class FormComponent_interface_string extends FormComponent_abstract
{
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

     RequestHandler::getInstance()->tpl->set( "interface_data", $data );

     $result = FormComponent_abstract::Interface_Parse();

     return RequestHandler::getInstance()->tpl->parse( $this->field->form->config["template_prefix_interface"].
                                      $this->field->config["interface_tpl"] );

   }
   // преобразование из поста в массив для загрузки моделью
   function Interface_PostToArray( $post_data )
   {
      return array(
                $this->field->name => rtrim($post_data["_".$this->field->name]),
                   );
   }

// EOC{ FormComponent_interface_string }
}


?>