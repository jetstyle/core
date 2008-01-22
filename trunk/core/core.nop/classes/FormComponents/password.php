<?php
/*

  Форм-процессор
  * see http://in.jetstyle.ru/rocket/rocketforms

  FormComponent_password( &$config )
      - $field -- $field->config instance-a поля  

  -------------------

  * validator   : проверяет парность пароля и не даёт вводить пустые значения
                  "validator_params" => { "min" => 5, }
  * interface   : генерирует два поля для ввода, осуществляет их разбор и md5()

  -------------------

  // Валидатор
  * Validate()

  // Интерфейс (парсинг и обработка данных)
  * Interface_SafeDataValue( $data_value )
  * Interface_Parse()
  * Interface_PostToArray( $post_data )

================================================================== v.0 (kuso@npj)
*/
$this->UseClass("FormComponents/model_plain");

class FormComponent_password extends FormComponent_model_plain
{
   // VALIDATOR ==============================================================================
   function Validate()
   {
     FormComponent_abstract::Validate();

     if ($this->field->config["password_optional"] &&
         ($this->post_value1 == "") && ($this->post_value2 == ""))
     {
       $this->valid = true;
     }
     else
     {
       if ($this->post_value1 == "")
         if ($this->post_value2 == "")
           $this->_Invalidate( "empty", "Поле обязательно для заполнения" );
         else
           $this->_Invalidate( "password_empty", "Заполните оба поля, пожалуйста" );
       else
       if ($this->post_value2 == "")
         $this->_Invalidate( "password_empty", "Заполните оба поля, пожалуйста" );
  
       if ($this->valid) // если всё ещё хорошо
       {
         if (isset($this->validator_params["min"]))
           if (strlen($this->post_value1) < $this->validator_params["min"])
             $this->_Invalidate( "string_short", "Слишком короткое значение" );
         
         if ($this->post_value1 != $this->post_value2)
           $this->_Invalidate( "password_diff", "Введённые вами значения не совпадают" );
       }
     }

     return $this->valid;
   }

   // MODEL ==============================================================================
   // ---- работа с хранилищем в массиве ----
   function Model_LoadFromArray( $a )
   {
     // если в массиве нет этого поля, значит и забирать его из массива не надо!
     if (!isset($a[ $this->field->name ])) return;
     else return parent::Model_LoadFromArray( $a );
   }


   // INTERFACE ==============================================================================
   // защита поля от "клиента"
   function Interface_SafeDataValue( $data_value )
   {
     return "******";
   }
   // парсинг полей интерфейса
   function Interface_Parse()
   {
     // никаких значений у поля быть не может!
     $result = FormComponent_abstract::Interface_Parse();
     
     return $this->field->tpl->Parse( $this->field->form->config["template_prefix_interface"].
                                      $this->field->config["interface_tpl"] );
   }
   // преобразование из поста в массив для загрузки моделью
   function Interface_PostToArray( $post_data )
   {
      // 1. получить из п
      $this->post_value1 = rtrim($post_data["_".$this->field->name."_1"]);
      $this->post_value2 = rtrim($post_data["_".$this->field->name."_2"]);

      if ($this->field->config["password_optional"] && $this->post_value1 == "")
        return array();
      else
        return array(
                $this->field->name => md5($this->post_value1),
                   );

   }

// EOC{ FormComponent_password }
}  
   

?>