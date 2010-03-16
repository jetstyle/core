<?php
/*

  ����-���������
  * see http://in.jetstyle.ru/rocket/rocketforms

  FormComponent_wrapper_group( &$config )
      - $field -- $field->config instance-a ����  

  -------------------

  * wrapper     : ������� ������ "������������� ������"

  -------------------

  // ������� (���������� � �����������)

  * Wrapper_Parse( $field_content )

================================================================== v.0 (kuso@npj)
*/
Finder::UseClass( "forms/components/wrapper_field" );

class FormComponent_wrapper_group extends FormComponent_wrapper_field
{
   // WRAPPER ===========================================================================
   // ���������� ������ ����
   function Wrapper_Parse( $field_content )
   {
     if (!$this->field->validator->valid) $this->field->config["wrapper_collapsed"]=false;
     $tpl = &Locator::get('tpl');
     $tpl->set( "is_collapsed", isset($this->field->config["wrapper_collapsed"]) && $this->field->config["wrapper_collapsed"] ? 1 : 0 );
     
     return FormComponent_wrapper_field::Wrapper_Parse( $field_content );
   }

// EOC{ FormComponent_wrapper_group }
}  
   

?>
