<?php
/*

  Форм-процессор
  * see http://in.jetstyle.ru/rocket/rocketforms

  FormComponent_validator_base( &$config )
      - $field -- $field->config instance-a поля  

  -------------------

  * validator   : базовый. Умеет только 
                    "validator_params" => "not_empty"
                    "validator_func"   => "user_func"( value, field_config )

  -------------------

  // Валидатор

  * Validate()

================================================================== v.0 (kuso@npj)
*/

class FormComponent_validator_base extends FormComponent_abstract
{
   // VALIDATOR ==============================================================================
   function Validate()
   {
     FormComponent_abstract::Validate();

     if ($this->validator_params["not_empty"])
       if ($this->field->model->Model_GetDataValue() == "")
         $this->_Invalidate( "empty", "Поле обязательно для заполнения" );

     if ($this->field->config["validator_func"])
       if ($result = call_user_func( $this->field->config["validator_func"], 
                                     $this->field->model->Model_GetDataValue(),
                                     $this->field->config ))
         $this->_Invalidate( "func", $result );

     return $this->valid;
   }

// EOC{ FormComponent_validator_base }
}  
   

?>