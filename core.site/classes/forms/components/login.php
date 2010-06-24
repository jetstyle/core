<?php
Finder::useClass("forms/components/validator_base");

class FormComponent_login extends FormComponent_validator_base
{
	// VALIDATOR ==============================================================================
	function Validate()
	{
		FormComponent_validator_base::Validate();

		if (!$this->valid) return $this->valid;

		if($this->field->config['validator_params']['not_exists'])
		{        	$value = $this->field->model->Model_GetDataValue();

			$model = clone Locator::get('principal')->getStorageModel();
			$model->loadByLogin($value);

			if ($model->getId() && Locator::get('principal')->getId() != $model->getId())
			{
				$this->_Invalidate( "duplicate_login", "Выбранный логин уже занят" );
				$this->valid = false;
			}
		}

		return $this->valid;
	}
}
?>
