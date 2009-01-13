<?php
/*

  ����-���������
  * see http://in.jetstyle.ru/rocket/rocketforms

  FormComponent_wrapper_field( &$config )
      - $field -- $field->config instance-a ����

  -------------------

  * wrapper     : ������� ������ "����"

  -------------------

  // ������� (���������� � �����������)

  * Wrapper_Parse( $field_content )

================================================================== v.0 (kuso@npj)
*/

class FormComponent_wrapper_field extends FormComponent_abstract
{
   // WRAPPER ===========================================================================
   // ���������� ������ ����
   function Wrapper_Parse( $field_content )
   {
     // ���� ���� ������?
     $tpl = Locator::get('tpl');
     $tpl->Set( "errors", "" );
     $tpl->set( "is_valid", $this->field->validator->valid );
     if (!$this->field->validator->valid)
     {
       $msgs = array();
       if (is_array($this->field->validator->validator_messages))
       {
         foreach( $this->field->validator->validator_messages as $msg=>$text )
          $msgs[] = array( "msg" => $msg, "text" => $text );
         $tpl->set('msgs',$msgs);
         $tpl->parse($this->field->form->config["template_prefix"]."errors.html:List",'errors');
       }
       else $tpl->Set( "errors", "" );
     }

     // ������ ������
     $tpl->set( "field", "_".$this->field->name ); // �� ������ ������

     $tpl->set(
     		"not_empty",
     		isset($this->field->config["validator_params"]["not_empty"]) && $this->field->config["validator_params"]["not_empty"] ? 1 : 0
     );

     $tpl->set( "content",        $field_content  );
     $tpl->set( "wrapper_title",  isset($this->field->config["wrapper_title"]) && $this->field->config["wrapper_title"] ? $this->field->config["wrapper_title"] : "" );
     $tpl->set( "wrapper_desc",   isset($this->field->config["wrapper_desc"]) && $this->field->config["wrapper_desc"] ? $this->field->config["wrapper_desc"] : "" );

     return $tpl->parse(
     		(isset($this->field->form->config["template_prefix_wrappers"]) ? $this->field->form->config["template_prefix_wrappers"] : "" ).
        (isset($this->field->config["wrapper_tpl"]) ? $this->field->config["wrapper_tpl"] : "" )
     );
   }

// EOC{ FormComponent_wrapper_field }
}


?>
