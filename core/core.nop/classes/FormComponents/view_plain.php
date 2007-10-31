<?php
/*

  Форм-процессор
  * see http://in.jetstyle.ru/rocket/rocketforms

  FormComponent_view_plain( &$config )
      - $field -- $field->config instance-a поля  

  -------------------

  * view        : Выводит поле, оформляя его в шаблон

  -------------------

  // Парсинг (оформление и отображение)

  * View_Parse()

================================================================== v.0 (kuso@npj)
*/

class FormComponent_view_plain extends FormComponent_abstract
{
   // VIEW ==============================================================================
   // парсинг readonly значения
   function View_Parse( $plain_data=NULL )
   {
     if ($plain_data !== NULL)
       $data = $plain_data;
     else
       $data = $this->field->model->Model_GetDataValue();

     if ($this->field->config["view_tpl"])
     {
       $this->field->tpl->Set( "view_prefix",  isset($this->field->config["view_prefix"]) ? $this->field->config["view_prefix"] : "" );
       $this->field->tpl->Set( "view_postfix", isset($this->field->config["view_postfix"]) ? $this->field->config["view_postfix"] : "" );
       $this->field->tpl->Set( "view_data",    $data );

       $data = $this->field->tpl->Parse( $this->field->form->config["template_prefix_views"].
                                         $this->field->config["view_tpl"] );
     }
     else // вариант для бедных
     {
       if ($this->field->config["view_prefix"])
         $data= $this->field->config["view_prefix"].$data;
       if ($this->field->config["view_postfix"])
         $data= $this->field->config["view_postfix"].$data;
     }
     return $data;
   }

// EOC{ FormComponent_view_plain }
}  
   

?>