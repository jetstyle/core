<?php

Finder::useClass('forms/FormField');
class CheckboxField extends FormField
{
    // ������� ����� ����������
    function Interface_Parse()
    {
        $data = $this->Model_GetDataValue();
        
        $tpl = Locator::get('tpl');
    
        $result = parent::Interface_Parse();
        
        $tpl->set('interface_data', $this->config['checkbox_value'] || 1);
        $tpl->set('checked', $data);
    
        return $tpl->parse($this->form->config['template_prefix_interface'].$this->config['interface_tpl']);
    }
  
    // �������������� �� ����� � ������ ��� �������� �������
    function Interface_PostToArray($post_data)
    {
        return array($this->name => rtrim($post_data['_'.$this->name]));
    }
}

?>
