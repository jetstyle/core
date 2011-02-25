<?php
Finder::useClass("forms/FormField");

class UserEmailField extends FormField
{
    function validate() {
        parent::validate();

        if (!$this->valid) return $this->valid;

        $value = $this->Model_GetDataValue();

        if($this->config['own_validator_params']['not_exists'])
        {
            $model = clone Locator::get('principal')->getStorageModel();
            $model->loadByEmail($value);
            if ($model->getId() && Locator::get('principal')->getId() != $model->getId())
            {
                $this->_Invalidate( "duplicate_login", "Выбранный email уже использовался для регистрации" );
                $this->valid = false;
            }
        }
        else if ($this->field->config['own_validator_params']['already_exists'])
        {
            $model = clone Locator::get('principal')->getStorageModel();
            $model->loadByEmail($value);
            if (!$model->getId())
            {
                $this->_Invalidate( "login_does_not_exists", "Пользователь с введённым адресом электронной почты не найден" );
                $this->valid = false;
            }
        }

        return $this->valid;
    }
}
?>
