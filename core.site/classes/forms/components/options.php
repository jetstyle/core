<?php
/*

  Форм-процессор
  * see http://in.jetstyle.ru/rocket/rocketforms

  FormComponent_options( &$config )
      - $field -- $field->config instance-a поля  

  -------------------

  * interface   : ПРОСТОЙ ВЫБОР. Выбор из списка через select или radio.
  * view        : ПРОСТОЙ ВЫБОР. Выводит значение для ключа из field->config["options"], оформляя его в шаблон.

  -------------------

  // Интерфейс (парсинг и обработка данных)
  
  * Interface_Parse()
  * Interface_PostToArray( $post_data )
  * View_Parse( $plain_data=NULL )

================================================================== v.0 (kuso@npj)
*/

Finder::UseClass("forms/components/view_plain");

class FormComponent_options extends FormComponent_view_plain
{
   // INTERFACE ==============================================================================
   // парсинг полей интерфейса
   function Interface_Parse()
   {
     $data = $this->field->model->Model_GetDataValue();
     
     //пометка выбранного - в зависимости от типа
     $selected_mark = $this->field->config["options_mode"]=="radio" ? "checked=\"checked\"" : "selected=\"selected\"";
     
     //формируем опции для отображения
     $A1 = $this->field->config["options"];
     $A2 = array();
     foreach($A1 as $v=>$t){
        $r["value"] = $v;
        $r["title"] = $t;
        $r["selected"] = $data==$v ? $selected_mark : "";
        $A2[] = $r;
     }
     
     //собственно рендеринг
     $result = FormComponent_abstract::Interface_Parse();
     Locator::get("tpl")->set("_options", $A2);
     return Locator::get("tpl")->parse( $this->field->form->config["template_prefix_interface"].
                                      $this->field->config["interface_tpl"] );
   }
   // преобразование из поста в массив для загрузки моделью
   function Interface_PostToArray( $post_data )
   {
      return array(
                $this->field->name => rtrim(@$post_data["_".$this->field->name]), //IVAN
                   );
   }

   // VIEW ==============================================================================
   // парсинг readonly значения
   function View_Parse( $plain_data=NULL ){
     $data = $this->field->config["options"][ $plain_data!=NULL ? $plain_data : $this->field->model->Model_GetDataValue() ];
     return FormComponent_view_plain::View_Parse($data);
   }

   
// EOC{ FormComponent_options }
}  
   

?>
