<?php

Finder::useClass('forms/FormField');
class CheckboxField extends FormField
{
    // парсинг полей интерфейса
    function Interface_Parse()
    {
        $data = $this->Model_GetDataValue();
        
        $tpl = Locator::get('tpl');
    
        $result = parent::Interface_Parse();
        
        $tpl->set('interface_data', $this->config['checkbox_value'] || 1);
        $tpl->set('checked', $data);
    
        return $tpl->parse($this->form->config['template_prefix_interface'].$this->config['interface_tpl']);
    }
  
    // преобразование из поста в массив для загрузки моделью
    function Interface_PostToArray($post_data)
    {
        return array($this->name => rtrim($post_data['_'.$this->name]));
    }
}

?>
