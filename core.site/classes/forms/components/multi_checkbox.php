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

     if(isset($db_row[ $this->field->name ]))
     {
       /*
       $model = $this->field->form->getModel();
       $foreignFields = $model->getForeignFields();
       //var_dump($foreignFields);
       //echo '<hr>';
       //var_dump($db_row);
       //var_dump( $foreignFields[$this->field->name] );
       foreach ( $db_row[ $this->field->name ] as $rubric )
       {       
          $rubric_ids[] = $rubric["rubric_id"];
          
       }
       $this->current_rubrics = $rubric_ids;
       
       $rubrics = DBModel::factory( $this->field->config[ "model_rubrics" ] )->registerObserver("row", array($this,"onRubricsRow"))->load()->getArray();
       */
       /**
        * model_rubrics should be linked to items and contain virtual field "checked"
        */
        $rubricsModel = $this->getModelRubrics();
        $foreignConf = $rubricsModel->getForeignFieldConf("items");
        $rubricsModel->removeField("items");        	

        	$rubricsModel->addField('>items', array(
			'model' => $foreignConf["className"],
			'pk' => $foreignConf["pk"],
			'fk' => $foreignConf["fk"],
			'join_where' => '{items.item_id} = '.DBModel::quote($this->field->form->data_id),
		));

        echo $rubrics = $rubricsModel->load();

       $this->Model_SetDataValue($rubrics);
     }
     else
      $this->Model_SetDefault();
   }
   
   protected function getModelRubrics(){
      if (! $this->modelRubrics )
        $this->modelRubrics = DBModel::factory( $this->field->config[ "model_rubrics" ] );

      return $this->modelRubrics;
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
       

        $rubricsModel = $this->getModelRubrics();
        $foreignModel = $rubricsModel->getForeignModel("items");
        $foreignConf  = $rubricsModel->getForeignFieldConf("items");
      var_dump( $foreignModel->getAllFields() );
die();
        $foreignModel->delete("item_id=". $data_id);
        ##DBModel::factory( $this->field->config[ "model_links" ] )->;
        foreach ( $this->model_data as $rubric_id )
        {
            $data = array( $foreignConf["fk"] =>$rubric_id, "item_id"=>$data_id);
            DBModel::factory( $this->field->config[ "model_links" ]  )->insert( $data );
        }
   }
   
   function Model_DbAfterUpdate( $data_id ){
        $this->Model_DbAfterInsert($data_id);
   }

   
   //Observer for marks selected
   public function onRubricsRow($model, $row){
        if (@in_array($row["id"], $this->current_rubrics))
            $row["checked"] = "checked";
   }
}

?>
