<?php
/*

  Форм-процессор
  * see http://in.jetstyle.ru/rocket/rocketforms

  FormComponent_validator_string( &$config )
      - $field -- $field->config instance-a поля

  -------------------

  * validator   : для строк. Умеет:
                  + <validator_base>
                  + is_numeric
                  + is_regexp = "/^[0-9a-z]*$/i"
                  + min
                  + max
                  + is_email
                  + is_http

                 (-) -- not implemented yet.

  -------------------

  // Валидатор

  * Validate()

================================================================== v.1 (kuso@npj)
*/
Finder::useClass( "forms/components/validator_base" );

class FormComponent_validator_string extends FormComponent_validator_base
{
   // VALIDATOR ==============================================================================
   function Validate()
   {
     FormComponent_validator_base::Validate();

     if (!$this->valid) return $this->valid; // ==== strip one

     $value = $this->field->model->Model_GetDataValue();

     if (isset($this->validator_params["min"]))
     {
       if (strlen($value) < $this->validator_params["min"])
         $this->_Invalidate( "string_short", "Слишком короткое значение" );
     }
     if (isset($this->validator_params["max"]))
       if (strlen($value) > $this->validator_params["max"])
         $this->_Invalidate( "string_long", "Слишком длинное значение" );

     if (!$this->valid) return $this->valid; // ==== strip two

     if (isset($this->validator_params["is_numeric"]))
       if (!is_numeric($value) && !empty($value))
         $this->_Invalidate( "not_number", "Значение должно быть числом" );

     if (isset($this->validator_params["is_regexp"]))
       if (!preg_match( $this->validator_params["is_regexp"], $value ))
         $this->_Invalidate( "not_regexp", "Значение не удовлетворяет формату" );

     $email = false; $http = false;
     if (!empty($value))
     {
       if (isset($this->validator_params["is_email"]))
         if (preg_match("/^(([a-z\.\-\_0-9+]+)@([a-z\.\-\_0-9]+\.[a-z]+))$/i", $value ))
           $email = true;
       if (isset($this->validator_params["is_http"]))
         if (preg_match("/^((ht|f)tp(s?):\/\/)?(([!a-z\-_0-9]+)\.)+([a-z0-9]+)(:[0-9]+)?(\/[=!~a-z\.\-_0-9\/?&%#]*)?$/i", $value ))
           $http = true;

       if (isset($this->validator_params["is_email"]) && isset($this->validator_params["is_http"])
           && !$email && !$http)
           $this->_Invalidate( "not_email_or_http", "Значение должно быть адресом электронной почты или сайта" );
       else
       if (isset($this->validator_params["is_email"]) && !$email)
           $this->_Invalidate( "not_email", "Значение должно быть адресом электронной почты" );
       else
       if (isset($this->validator_params["is_http"]) && !$http)
           $this->_Invalidate( "not_http", "Значение должно быть адресом сайта" );
       if (isset($this->validator_params["is_date"]))
       {
         $dateArray = strptime($value, '%d.%m.%Y');
         if ($dateArray === false)
         {
            $this->_Invalidate( "date_wrong", "Неверный формат даты" );
         }   
       }
     }

     return $this->valid;
   }

// EOC{ FormComponent_validator_base }
}


?>
