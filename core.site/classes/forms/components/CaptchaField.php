<?php
Finder::UseClass( "forms/FormField" );

class CaptchaField extends FormField
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

        $value = $this->Model_GetDataValue();

        @session_start();
        if(!isset($_SESSION['captcha_keystring']) || !$value || ($_SESSION['captcha_keystring'] != $value))
        {
			$this->_Invalidate( "captcha_err", "Символы введены неверно" );
		}

        return $this->valid;
    }
}
?>