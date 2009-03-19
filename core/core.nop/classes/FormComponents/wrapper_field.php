<?php
/*

  Форм-процессор
  * see http://in.jetstyle.ru/rocket/rocketforms

  FormComponent_wrapper_field( &$config )
      - $field -- $field->config instance-a поля  

  -------------------

  * wrapper     : обычная обёртка "поля"

  -------------------

  // Парсинг (оформление и отображение)

  * Wrapper_Parse( $field_content )

================================================================== v.0 (kuso@npj)
*/

class FormComponent_wrapper_field extends FormComponent_abstract
{
   // WRAPPER ===========================================================================
   // оформление вокруг поля
   function Wrapper_Parse( $field_content )
   {
     $this->field->tpl->Set( "errors", "" );
     // если есть ошибки?
     $this->field->tpl->Set( "is_valid", $this->field->validator->valid );
     if (!$this->field->validator->valid)
     {
       $msgs = array();

       if (is_array($this->field->validator->validator_messages))
       {
         foreach( $this->field->validator->validator_messages as $msg=>$text )
	    $msgs[] = array( "msg" => $msg, "text" => $text );

         $this->field->tpl->Loop( $msgs, $this->field->form->config["template_prefix"]."errors.html:List", "errors" );
	 //var_dump( $this->field->form->config["template_prefix"]."errors.html:List" );
       }
         
      // var_dump( $this->field->validator->validator_messages );
     }

     // парсим обёртку
     $this->field->tpl->Set( "field", "_".$this->field->name ); // на всякий случай

     $this->field->tpl->Set( 
     		"not_empty", 
     		isset($this->field->config["validator_params"]["not_empty"]) && $this->field->config["validator_params"]["not_empty"] ? 1 : 0 
     );

     $this->field->tpl->Set( "content",        $field_content  );
     $this->field->tpl->Set( "wrapper_title",  isset($this->field->config["wrapper_title"]) && $this->field->config["wrapper_title"] ? $this->field->config["wrapper_title"] : "" );
     $this->field->tpl->Set( "wrapper_desc",   isset($this->field->config["wrapper_desc"]) && $this->field->config["wrapper_desc"] ? $this->field->config["wrapper_desc"] : "" );

     return $this->field->tpl->Parse( 
     		(isset($this->field->form->config["template_prefix_wrappers"]) ? $this->field->form->config["template_prefix_wrappers"] : "" ).
        (isset($this->field->config["wrapper_tpl"]) ? $this->field->config["wrapper_tpl"] : "" )
     );
   }

// EOC{ FormComponent_wrapper_field }
}  
   

?>