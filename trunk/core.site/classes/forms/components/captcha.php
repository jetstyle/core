<?php
Finder::UseClass( "forms/components/model_plain" );

class FormComponent_captcha extends FormComponent_model_plain
{
    // MODEL ==============================================================================
    function Model_DbInsert( &$fields, &$values )
    {

    }

    function Model_DbUpdate( $data_id, &$fields, &$values )
    {

    }

    // VALIDATOR ==============================================================================
    function Validate()
    {
        parent::Validate();

        if (!$this->valid) return $this->valid; // ==== strip one

        $value = $this->field->model->Model_GetDataValue();

        @session_start();
        if(!isset($_SESSION['captcha_keystring']) || !$value || ($_SESSION['captcha_keystring'] != $value))
        {
			$this->_Invalidate( "captcha_err", "Символы введены неверно" );
		}

        return $this->valid;
    }
}
?>