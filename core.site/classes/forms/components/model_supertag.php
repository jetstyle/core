<?php

class FormComponent_model_supertag extends FormComponent_model_plain
{
    // возврат значения в виде "шифра" или "ключа"
    function Model_GetDataValue()
    {
        if (strlen($this->model_data) == 0)
        {
            Finder::useClass('Translit');
			$translit = new Translit();
			return $translit->supertag($this->field->form->getFieldByName($this->field->config['supertag_from'])->model->Model_GetDataValue(), 40);
        }
        else
        {
            return $this->model_data;    
        }
    }
}  
   

?>