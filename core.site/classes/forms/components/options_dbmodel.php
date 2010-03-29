<?php
Finder::UseClass("forms/components/options_sql");

class FormComponent_options_dbmodel extends FormComponent_options_sql
{
    function _PrepareOptions()
    {
        $options = DBModel::factory($this->field->config["options_dbmodel"])->load();
        $data = array();
        foreach( $options as $k=>$v )
        {
            $data[ $v["id"] ] = $v["title"];
        }
        $this->field->config["options"] = isset($this->field->config["options"]) ? $this->field->config["options"] + $data : $data;
    }
}


?>
