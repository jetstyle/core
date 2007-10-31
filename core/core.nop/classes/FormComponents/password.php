<?php
/*

  ����-���������
  * see http://in.jetstyle.ru/rocket/rocketforms

  FormComponent_password( &$config )
      - $field -- $field->config instance-a ����  

  -------------------

  * validator   : ��������� �������� ������ � �� ��� ������� ������ ��������
                  "validator_params" => { "min" => 5, }
  * interface   : ���������� ��� ���� ��� �����, ������������ �� ������ � md5()

  -------------------

  // ���������
  * Validate()

  // ��������� (������� � ��������� ������)
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
           $this->_Invalidate( "empty", "���� ����������� ��� ����������" );
         else
           $this->_Invalidate( "password_empty", "��������� ��� ����, ����������" );
       else
       if ($this->post_value2 == "")
         $this->_Invalidate( "password_empty", "��������� ��� ����, ����������" );
  
       if ($this->valid) // ���� �� ��� ������
       {
         if (isset($this->validator_params["min"]))
           if (strlen($this->post_value1) < $this->validator_params["min"])
             $this->_Invalidate( "string_short", "������� �������� ��������" );
         
         if ($this->post_value1 != $this->post_value2)
           $this->_Invalidate( "password_diff", "�������� ���� �������� �� ���������" );
       }
     }

     return $this->valid;
   }

   // MODEL ==============================================================================
   // ---- ������ � ���������� � ������� ----
   function Model_LoadFromArray( $a )
   {
     // ���� � ������� ��� ����� ����, ������ � �������� ��� �� ������� �� ����!
     if (!isset($a[ $this->field->name ])) return;
     else return parent::Model_LoadFromArray( $a );
   }


   // INTERFACE ==============================================================================
   // ������ ���� �� "�������"
   function Interface_SafeDataValue( $data_value )
   {
     return "******";
   }
   // ������� ����� ����������
   function Interface_Parse()
   {
     // ������� �������� � ���� ���� �� �����!
     $result = FormComponent_abstract::Interface_Parse();
     
     return $this->field->tpl->Parse( $this->field->form->config["template_prefix_interface"].
                                      $this->field->config["interface_tpl"] );
   }
   // �������������� �� ����� � ������ ��� �������� �������
   function Interface_PostToArray( $post_data )
   {
      // 1. �������� �� �
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