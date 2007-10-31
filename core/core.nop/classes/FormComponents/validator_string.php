<?php
/*

  ����-���������
  * see http://in.jetstyle.ru/rocket/rocketforms

  FormComponent_validator_string( &$config )
      - $field -- $field->config instance-a ����  

  -------------------

  * validator   : ��� �����. �����:
                  + <validator_base>
                  + is_numeric
                  + is_regexp = "/^[0-9a-z]*$/i"
                  + min
                  + max
                  - is_email
                  - is_http

                 (-) -- not implemented yet.

  -------------------

  // ���������

  * Validate()

================================================================== v.1 (kuso@npj)
*/
$this->UseClass( "FormComponents/validator_base" );

class FormComponent_validator_string extends FormComponent_validator_base
{
   // VALIDATOR ==============================================================================
   function Validate()
   {
     FormComponent_validator_base::Validate();

     if (!$this->valid) return $this->valid; // ==== strip one

     $value = $this->field->model->Model_GetDataValue();

     if (isset($this->validator_params["min"]))
       if (strlen($value) < $this->validator_params["min"])
         $this->_Invalidate( "string_short", "������� �������� ��������" );

     if (isset($this->validator_params["max"]))
       if (strlen($value) > $this->validator_params["max"])
         $this->_Invalidate( "string_long", "������� ������� ��������" );

     if (!$this->valid) return $this->valid; // ==== strip two

     if (isset($this->validator_params["is_numeric"]))
       if (!is_numeric($value) && !empty($value))
         $this->_Invalidate( "not_number", "�������� ������ ���� ������" );

     if (isset($this->validator_params["is_regexp"]))
       if (!preg_match( $this->validator_params["is_regexp"], $value ))
         $this->_Invalidate( "not_regexp", "�������� �� ������������� �������" );

     return $this->valid;
   }

// EOC{ FormComponent_validator_base }
}  
   

?>