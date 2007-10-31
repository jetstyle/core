<?php

class FormComponent_file extends FormComponent_abstract
{
  function Model_UploadFile($data_id)
  {

    $this->field->rh->UseClass('Upload');
    $file =& new Upload($this->field->rh, $this->field->config['model_data_dir'] ? $this->field->config['model_data_dir'] : 'files/');
    
//print_r($this->field->config);die($this->field->config['model_data_dir'] ? $this->field->config['model_data_dir'] : 'files/');
    // валидация
    if (isset($this->field->config['validator_params']['allow']))
      $file->ALLOW = $this->field->config['validator_params']['allow'];
    if (isset($this->field->config['validator_params']['deny']))
      $file->DENY = $this->field->config['validator_params']['deny'];
// загружаем файл
    $filename = $this->field->config['model_data_name'] ? $this->Model_GetDataName($data_id) : $data_id;
    
    $file->UploadFile('_'.$this->field->name, $filename);
  }
  
  /*
   * аплоад с ресайзом
   */  
  function Model_UploadFileResize($data_id)
  {
    $this->field->rh->UseClass('Upload');
    //die($this->field->config['model_data_dir'] ? $this->field->config['model_data_dir'] : 'files/');
    $file =& new Upload($this->field->rh, $this->field->config['model_data_dir'] ? $this->field->config['model_data_dir'] : 'files/');
    $filename = ($this->field->config['model_data_name'] ? $this->Model_GetDataName($data_id) : $data_id)."_".$this->field->config['model_data_resize'][0]."x".$this->field->config['model_data_resize'][1];
    $file->UploadFile('_'.$this->field->name, $filename, false, $this->field->config['model_data_resize'], true, false);
  }
  
  function Model_GetDataName($data_id)
  {
    $str = str_replace("*", $data_id, $this->field->config['model_data_name']);
    return  str_replace ("%id%", $data_id, $str);
  }

  function Model_DbAfterInsert( $data_id )
  { $this->Model_UploadFile($data_id); }
  function Model_DbAfterUpdate( $data_id )
  { 
    //делаем ресайз картинки, если надо
    if (is_array($this->field->config['model_data_resize']))
    {
        $this->Model_UploadFileResize($data_id);
    }
    //аплодим оригинал
    $this->Model_UploadFile($data_id); 
  }

  function Model_GetDataValue()
  {
     return $this->model_data;
  }

   // ---- работа с БД ----
   function Model_DbLoad( $db_row )
   { 
     if(isset($db_row['id']))
       $this->model_data = $db_row['id']; //$this->field->name
     else
      $this->Model_SetDefault();
   }
   
   function Model_DbUpdate( $data_id, &$fields, &$values )
   {
     if ($_POST['_'.$this->field->name.'_del']==="1")
     {
            $this->field->rh->UseClass('Upload');
            $upload =& new Upload($this->field->rh, $this->field->config['model_data_dir'] ? $this->field->config['model_data_dir'] : 'files/');

            
            //$fname = $data_id . ($this->field->config['model_data_name'] == "*_preview" ? "_preview" : "");
            $fname = $data_id . (!empty($this->field->config['model_data_name']) ? str_replace('*', '', $this->field->config['model_data_name']) : '');
            if ($upload->GetFile($fname))
            {
                unlink( $upload->current->name_full );
            }

     }
     return $this->Model_DbInsert( $fields, $values );
   }
}

?>