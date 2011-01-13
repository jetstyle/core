<?php
/*

  Форм-процессор
  * see http://in.jetstyle.ru/quickstart/formstories

  FormComponent_interface_string( &$config )
      - $field -- $field->config instance-a поля  

  -------------------

  * interface   : ЧЕКБОКС, МНОГО. интерфейс выбора

  -------------------

  // Интерфейс (парсинг и обработка данных)
  
  * Interface_Parse()
  * Interface_PostToArray( $post_data )

================================================================== v.1 (nop@jetstyle.ru)
*/

class FormComponent_multi_checkbox extends FormComponent_abstract
{
  // парсинг полей интерфейса
  function Interface_Parse()
  {
    $data = $this->field->model->Model_GetDataValue();

    Locator::get('tpl')->Set('interface_data', $this->field->config['checkbox_value']);
    Locator::get('tpl')->Set('checked', $data);
    
    Locator::get('tpl')->Set('*', $data);

    $result = FormComponent_abstract::Interface_Parse();

    $ret = Locator::get('tpl')->Parse($this->field->form->config['template_prefix_interface'].
                                    $this->field->config['interface_tpl']);
    return $ret;
   }

  // преобразование из поста в массив для загрузки моделью
  function Interface_PostToArray($post_data)
  {
    return array($this->field->name => $post_data['_'.$this->field->name]);
  }

   // ---- работа с БД ----
   function Model_DbLoad( $db_row )
   {
     $model = $this->field->form->getModel();
     $foreignFields = $model->getForeignFields();
     //var_dump($foreignFields);
     //echo '<hr>';
     //var_dump($db_row);
     if(isset($db_row[ $this->field->name ]))
     {
       
       //var_dump( $foreignFields[$this->field->name] );
       foreach ( $db_row[ $this->field->name ] as $rubric )
       {       
          $rubric_ids[] = $rubric["rubric_id"];
          
       }
       $this->current_rubrics = $rubric_ids;
       
       $rubrics = DBModel::factory( $this->field->config[ "model_rubrics" ] )->registerObserver("row", array($this,"onRubricsRow"))->load()->getArray();

//       $this->model_data        = $db_row[ $this->field->name ];
//       $this->model_data        = $rubrics;
       $this->Model_SetDataValue($rubrics);
     }
     else
      $this->Model_SetDefault();
   }
   // возврат значения 
   function Model_GetDataValue()
   {
     return $this->model_data;
   }
   // изменение значения 
   function Model_SetDataValue($model_value)
   {
     $this->model_data        = $model_value;
   }
   // ---- работа с хранилищем в массиве ----
   function Model_LoadFromArray( $a )
   {
     $this->model_data        = $a[ $this->field->name ];
   }
   
   function Model_DbAfterInsert( $data_id ){
        DBModel::factory( $this->field->config[ "model_links" ] )->delete("item_id=".$data_id);
        foreach ($this->model_data as $rubric_id)
        {
            $data = array("rubric_id"=>$rubric_id, "item_id"=>$data_id);
            DBModel::factory( $this->field->config[ "model_links" ]  )->insert( $data );
        }
   }
   
   function Model_DbAfterUpdate( $data_id ){
        $this->Model_DbAfterInsert($data_id);
   }
   /*
   function Model_DbInsert( &$fields, &$values )
   {
     $fields[] = $this->field->name;
     $values[] = $this->model_data;
   }

   function Model_DbUpdate( $data_id, &$fields, &$values )
   {
     return $this->Model_DbInsert( $fields, $values );
   }
   */
   
   //Observer for marks selected
   public function onRubricsRow($model, $row){
        if (@in_array($row["id"], $this->current_rubrics))
            $row["checked"] = "checked";
   }
}

?>
