<?php
Finder::useClass("forms/components/validator_base");

class FormComponent_user_login_or_email extends FormComponent_validator_base {
// VALIDATOR ==============================================================================
    function Validate() {
        FormComponent_validator_base::Validate();

        if (!$this->valid) return $this->valid;
        $value = $this->field->model->Model_GetDataValue();

        $isEmail = true;
        $isLogin = true;

        if (!preg_match("/^(([a-z\.\-\_0-9+]+)@([a-z\.\-\_0-9]+\.[a-z]+))$/i", $value )) {
            $isEmail = false;
        }

        if (!preg_match("/^[a-z\-\_0-9]{".($this->field->config['validator_params']['min'] ? $this->field->config['validator_params']['min'] : 3).",}$/i", $value )) {
            $isLogin = false;
        }

        if (!$isEmail && !$isLogin)
        {
            $this->_Invalidate( "not_email", "Значение должно быть логином или адресом электронной почты" );
            $this->valid = false;
            return $this->valid;
        }

        if($this->field->config['validator_params']['already_exists']) {
            $isExistentEmail = false;
            $isExistentLogin = false;

            if ($isEmail)
            {
                $model = clone Locator::get('principal')->getStorageModel();
                $model->loadByEmail($value);

                if ($model->getId()) {
                    $isExistentEmail = true;
                }
            }

            if ($isLogin)
            {
                $model = clone Locator::get('principal')->getStorageModel();
                $model->loadByLogin($value);

                if ($model->getId()) {
                    $isExistentLogin = true;
                }
            }

            if (!$isExistentEmail && !$isExistentLogin)
            {
                $this->_Invalidate( "not_exist", "Пользователь не найден" );
                $this->valid = false;
                return $this->valid;
            }
        }

        return $this->valid;
    }
}
?>
