<?php
Finder::useClass("forms/components/validator_base");

class FormComponent_user_email extends FormComponent_validator_base {
// VALIDATOR ==============================================================================
    function Validate() {
        FormComponent_validator_base::Validate();

        if (!$this->valid) return $this->valid;

        $value = $this->field->model->Model_GetDataValue();
        if (!preg_match("/^(([a-z\.\-\_0-9+]+)@([a-z\.\-\_0-9]+\.[a-z]+))$/i", $value )) {
            $this->_Invalidate( "not_email", "Значение должно быть адресом электронной почты" );
            $this->valid = false;
            return $this->valid;
        }

        if($this->field->config['validator_params']['not_exists']) {


            $model = clone Locator::get('principal')->getStorageModel();
            $model->loadByEmail($value);

            if ($model->getId() && Locator::get('principal')->getId() != $model->getId()) {
                $this->_Invalidate( "duplicate_login", "Выбранный email уже использовался для регистрации" );
                $this->valid = false;
            }
        }

        if($this->field->config['validator_params']['already_exists']) {

            $model = clone Locator::get('principal')->getStorageModel();
            $model->loadByEmail($value);

            if (!$model->getId()) {
                $this->_Invalidate( "login_does_not_exists", "Пользователь с введённым адресом электронной почты не найден" );
                $this->valid = false;
            }
        }

        return $this->valid;
    }
}
?>
