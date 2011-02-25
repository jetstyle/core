<?php

Finder::UseClass("forms/FormField");

class OptionsField extends FormField
{
    function Interface_Parse()
    {
        $data = $this->Model_GetDataValue();
     
        //пометка выбранного - в зависимости от типа
        $selected_mark = $this->config["options_mode"]=="radio" ? "checked=\"checked\"" : "selected=\"selected\"";
     
        //формируем опции для отображения
        $options = $this->config["options"];
        $optionsHash = array();

        if (!is_array($options))
        {
            $optionsModel = DBModel::factory($options)->load()->getArray();
            $options = array();
            foreach ($optionsModel as $option)
            {
                $options[$option['id']] = $option['title'];
            }
        }
        $values = array();
        if (isset($this->config['default_value'])) {
            $values[] = array(
                "value" => $this->config['default_value'],
                "title" => $this->config['default_title'],
            );
        }
        foreach($options as $v => $t)
        {
            $r["value"] = $v;
            $r["title"] = $t;
            $r["selected"] = $data==$v ? $selected_mark : "";
            $values[] = $r;
        }
        
        $result = parent::Interface_Parse();
        Locator::get("tpl")->set("_options", $values);
        return Locator::get("tpl")->parse( $this->form->config["template_prefix_interface"].
                                      $this->config["interface_tpl"] );
    }
   
    function Interface_PostToArray( $post_data )
    {
        return array(
                $this->name => rtrim(@$post_data["_".$this->name]), //IVAN
                   );
    }

    function View_Parse( $plain_data=NULL ){
        $data = $this->config["options"][ $plain_data!=NULL ? $plain_data : $this->Model_GetDataValue() ];
        return parent::View_Parse($data);
    }
}  
   

?>