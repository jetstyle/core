<?php

class FormComponent_file extends FormComponent_abstract
{
  function Model_UploadFile($data_id)
  {
    $this->field->rh->UseClass('Upload');
//    $file =& new Upload($this->field->rh, 'files/');
    $file =& new Upload($this->field->rh, $this->field->config['model_data_dir'] ? $this->field->config['model_data_dir'] : 'files/');

    // валидация
    if (isset($this->field->config['validator_params']['allow']))
      $file->ALLOW = $this->field->config['validator_params']['allow'];
    if (isset($this->field->config['validator_params']['deny']))
      $file->DENY = $this->field->config['validator_params']['deny'];

    // загружаем файл
    $file->UploadFile('_'.$this->field->name, $this->field->config['model_data_name'] ? $this->field->config['model_data_name'] : $data_id);
  }

  function Model_DbAfterInsert( $data_id )
  { $this->Model_UploadFile($data_id); }
  function Model_DbAfterUpdate( $data_id )
  { $this->Model_UploadFile($data_id); }
}

?>