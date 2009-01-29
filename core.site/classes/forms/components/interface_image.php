<?php
/*

  Форм-процессор
  * see http://in.jetstyle.ru/rocket/rocketforms

  FormComponent_image( &$config )
      - $field -- $field->config instance-a поля

  -------------------

  * interface   : Картинка

  -------------------

  // Интерфейс (парсинг и обработка данных)

  * Interface_Parse()


================================================================== v.0 (nop)
*/

class FormComponent_interface_image extends FormComponent_abstract
{
   function Interface_Parse()
   {
     $_data = $this->field->model->Model_GetDataValue();
     $data  = $this->field->interface->Interface_SafeDataValue($_data);

     $result = FormComponent_abstract::Interface_Parse();

	 $file =& new Upload($this->field->rh, $this->field->config['model_data_dir'] ? $this->field->config['model_data_dir'] : 'files/');
	 if ($file->getFile($data))
     {
	     $this->field->tpl->Set('src', $this->field->rh->ri->_base_full.'pict.php?img='.$file->current['name_full']);
	     $this->field->tpl->Parse('forms/file.html:image', 'image');
	 //    $this->field->tpl->Set( "interface_data", $data );
	 }else
     {
	 	$this->field->tpl->set('image', '');
	 }

     return $this->field->tpl->Parse( $this->field->form->config["template_prefix_interface"].
                                      $this->field->config["interface_tpl"] );
    }

	function Interface_SafeDataValue($_data)
	{
	    return $_data. (!empty($this->field->config['model_data_name']) ? str_replace('*', '', $this->field->config['model_data_name']) : '' );
	}
}


?>