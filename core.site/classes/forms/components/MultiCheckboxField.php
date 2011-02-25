<?php

class MultiCheckboxField extends FormField
{
  // парсинг полей интерфейса
  function Interface_Parse()
  {
    $data = $this->Model_GetDataValue();

    Locator::get('tpl')->Set('interface_data', $this->config['checkbox_value']);
    Locator::get('tpl')->Set('checked', $data);
    
    Locator::get('tpl')->Set('*', $data);

    $result = parent::Interface_Parse();

    $ret = Locator::get('tpl')->Parse($this->form->config['template_prefix_interface'].
                                    $this->config['interface_tpl']);
    return $ret;
   }

  // преобразование из поста в массив для загрузки моделью
  function Interface_PostToArray($post_data)
  {
    return array($this->name => $post_data['_'.$this->name]);
  }

   // ---- работа с БД ----
   function Model_DbLoad( $db_row )
   {

     if(isset($db_row[ $this->name ]))
     {
       /**
        * model_rubrics should be linked to items and contain virtual field "checked"
        */
        $rubricsModel = $this->getModelRubrics();
        
        $foreignModelName = $this->getForeignModelName(); //items
        $foreignModelPK   = $this->getForeignModelPK();   //item_id
        
        $foreignConf = $rubricsModel->getForeignFieldConf($foreignModelName);
        $rubricsModel->removeField($foreignModelName);        	

        $rubricsModel->addField('>'.$foreignModelName, array(
			'model' => $foreignConf["className"],
			'pk' => $foreignConf["pk"],
			'fk' => $foreignConf["fk"],
			'join_where' => '{'.$foreignModelName.'.'.$foreignModelPK.'} = '.DBModel::quote($this->form->data_id),
		));

       $rubrics = $rubricsModel->load();

       $this->Model_SetDataValue($rubrics);
     }
     else
      $this->Model_SetDefault();
   }
   
   protected function getModelRubrics(){
      if (! $this->modelRubrics )
        $this->modelRubrics = DBModel::factory( $this->config[ "model_rubrics" ] );

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
     $this->model_data        = $a[ $this->name ];
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
   
   function dbUpdate() {
	
   }
   
   function dbInsert() {
	
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
