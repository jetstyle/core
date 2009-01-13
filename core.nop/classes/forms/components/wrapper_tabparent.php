<?php
/*

  Форм-процессор
  * see http://in.jetstyle.ru/rocket/rocketforms

  FormComponent_wrapper_tabparent( &$config )
      - $field -- $field->config instance-a поля  

  -------------------

  * wrapper     : обычная обёртка "группы для таб-групп"

  -------------------

  // Парсинг (оформление и отображение)

  * Wrapper_Parse( $field_content )

================================================================== v.0 (kuso@npj)
*/
Finder::UseClass( "forms/components/wrapper_group" );

class FormComponent_wrapper_tabparent extends FormComponent_wrapper_group
{
   // WRAPPER ===========================================================================
   // оформление вокруг поля
   function Wrapper_Parse( $field_content )
   {
     // отпарсиваем все нафиг поля для заголовков
     $tabs = array();
     foreach( $this->field->model->childs as $k=>$v )
      $tabs[] = array(
                    "wrapper_title" => isset($v->config["wrapper_title"]) ? $v->config["wrapper_title"] : "",
                    "wrapper_desc"  => isset($v->config["wrapper_desc"]) ? $v->config["wrapper_desc"] : "",
                    "is_valid"      => $v->validator->valid,
                    "field"         => $v->name,
                     );
     $this->field->tpl->Set( "parent", $this->field->name );
     $this->field->tpl->Loop( $tabs, $this->field->form->config["template_prefix_group"].
                                     $this->field->config["group_tpl"]."_Headers", "tab_controls" );

     // определяем первый таб быть выбранным
     if (!$this->field->validator->valid) $this->field->config["wrapper_collapsed"]=false;
     $this->field->tpl->Set( "is_collapsed", isset($this->field->config["wrapper_collapsed"]) && $this->field->config["wrapper_collapsed"] ? 1 : 0 );


     return FormComponent_wrapper_group::Wrapper_Parse( $field_content );
   }

// EOC{ FormComponent_wrapper_tabparent }
}  
   

?>
