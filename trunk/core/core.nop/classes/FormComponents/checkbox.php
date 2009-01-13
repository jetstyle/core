<?php
/*

  Форм-процессор
  * see http://in.jetstyle.ru/rocket/rocketforms

  FormComponent_interface_string( &$config )
      - $field -- $field->config instance-a поля  

  -------------------

  * interface   : ЧЕКБОКС. интерфейс выбора

  -------------------

  // Интерфейс (парсинг и обработка данных)
  
  * Interface_Parse()
  * Interface_PostToArray( $post_data )

================================================================== v.0 (http://ivan.shumkov.ru/)
*/

class FormComponent_checkbox extends FormComponent_abstract
{
  // парсинг полей интерфейса
  function Interface_Parse()
  {
    $data = $this->field->model->Model_GetDataValue();

    $this->field->tpl->Set('interface_data', $this->field->config['checkbox_value']);
    $this->field->tpl->Set('checked', $data);

    $result = FormComponent_abstract::Interface_Parse();

    return $this->field->tpl->Parse($this->field->form->config['template_prefix_interface'].
                                    $this->field->config['interface_tpl']);
   }

  // преобразование из поста в массив для загрузки моделью
  function Interface_PostToArray($post_data)
  {
    return array($this->field->name => @rtrim($post_data['_'.$this->field->name]));
  }
}

?>