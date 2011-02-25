<?php

Finder::UseClass("forms/components/OptionsField");

class OptionsSqlField extends OptionsField
{
    // получим список опций из БД
    function _PrepareOptions()
    {
        $options = Locator::get('db')->query( $this->config["options_sql"] );
        $data = array();
        foreach( $options as $k=>$v ) $data[ $v["id"] ] = $v["name"];
            $this->config["options"] = isset($this->config["options"]) ? $this->config["options"] + $data : $data;
    }

    // INTERFACE ==============================================================================
    // парсинг полей интерфейса
    function Interface_Parse()
    {
        $this->_PrepareOptions();
        return parent::Interface_Parse();
    }
   
    // преобразование из поста в массив для загрузки моделью
    function Interface_PostToArray( $post_data )
    {
        $this->_PrepareOptions();
        return parent::Interface_PostToArray( $post_data );
    }

    // VIEW ==============================================================================
    // парсинг readonly значения
    function View_Parse( $plain_data=NULL )
    {
        $this->_PrepareOptions();
        return parent::View_Parse( $plain_data );
    }
}


?>
