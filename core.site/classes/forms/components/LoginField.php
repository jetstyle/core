<?php
Finder::useClass("forms/FormField");

class LoginField extends FormField
{
	// VALIDATOR ==============================================================================
	function Validate()
	{
		parent::validate();

		if (!$this->valid) return $this->valid;

		if($this->config['own_validator_params']['not_exists'])
		{
        	$value = $this->Model_GetDataValue();

			$model = clone Locator::get('principal')->getStorageModel();
			$model->loadByLogin($value);

			if ($model->getId() && Locator::get('principal')->getId() != $model->getId())
			{
				$this->_Invalidate( "duplicate_login", "Выбранный логин уже занят" );
				$this->valid = false;
			}
		}
		
		if($this->config['own_validator_params']['already_exists'])
		{
        	$value = $this->Model_GetDataValue();

			$model = clone Locator::get('principal')->getStorageModel();
			$model->loadByLogin($value);

			if (!$model->getId())
			{
				$this->_Invalidate( "login_does_not_exists", "Пользователь с введённым логином не найден" );
				$this->valid = false;
			}
		}

		return $this->valid;
	}
}
?>
