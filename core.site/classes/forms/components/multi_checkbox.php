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
       /**
        * model_rubrics should be linked to items and contain virtual field "checked"
        */
        $rubricsModel = $this->getModelRubrics();
        
        $foreignModelName = $this->getForeignModelName(); //items
        $foreignModelPK   = $this->getForeignModelPK();   //item_id - should be always item_id, if you want to override with "model_links"
        
        $foreignConf = $rubricsModel->getForeignFieldConf($foreignModelName);
        $rubricsModel->removeField($foreignModelName);        	

        $rubricsModel->addField('>'.$foreignModelName, array(
			'model' => $this->field->config["model_links"] ? $this->field->config["model_links"] : $foreignConf["className"],
			'pk' => $foreignConf["pk"],
			'fk' => $foreignConf["fk"],
			'join_where' => '{'.$foreignModelName.'.'.$foreignModelPK.'} = '.DBModel::quote($this->field->form->data_id),
		));

       $rubrics = $rubricsModel->load();

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
   
   //autodetect foreign model with links
   function getForeignModelName()
   {
        $rubricsModel = $this->getModelRubrics();
        $foreignFields = $rubricsModel->getForeignFields();
        
        if ( count($foreignFields) ==1 )
        {
            $foreignModelName = array_pop( array_keys($foreignFields) );
        }
        if (!$foreignModelName)
            throw new JsException("Can`t autodetect foreign model for ".$rubricsModel->className." in ".__FILE__);
            
        return $foreignModelName;
   }
   
   function getForeignModelPK()
   {
        $rubricsModel = $this->getModelRubrics();
        $foreignModelName = $this->getForeignModelName();
        $foreignModel = $rubricsModel->getForeignModel($foreignModelName);
        $foreignConf  = $rubricsModel->getForeignFieldConf($foreignModelName);
        
        $links_fields = $foreignModel->getAllFields();
        $links_fields_assoc = array_flip($links_fields);
        unset($links_fields[ array_search($foreignConf["fk"], $links_fields) ] );
        $pk = array_pop($links_fields);
        
        if (empty($pk))
            $pk = "item_id";
            
        return $pk;
   }
   
   function Model_DbAfterInsert( $data_id ){
        $rubricsModel = $this->getModelRubrics();
        $foreignModelName = $this->getForeignModelName();
        $foreignModel = $rubricsModel->getForeignModel($foreignModelName);
        $foreignConf  = $rubricsModel->getForeignFieldConf($foreignModelName);

        /**
         * next code looks like shit, but all it does - extracts links_table.pk, which is not fk
         * item_id(pk) not rubric_id(fk)
         */    
        if (!empty($foreignConf["fk"]))
        {
            $pk = $this->getForeignModelPK();
        }
        else
            $foreignConf["fk"] = "rubric_id";

        //delete all links to this $data_id (item_id)
        $foreignModel->delete($pk."=". $data_id);

        foreach ( $this->model_data as $rubric_id )
        {
            $data = array( $foreignConf["fk"] =>$rubric_id, $pk=>$data_id);
            $foreignModel->insert( $data );
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
