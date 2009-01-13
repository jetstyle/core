<?php
/**
 * Класс FormController -- управляет формами
 */

$this->UseClass("controllers/Controller");
class FormController extends Controller
{
	var $form_fields = array();
	var $form_name = '';
	var $form_fields_sep = '_';
	var $form_method = 'POST';

	var $errors = array();

	function initialize(&$ctx, $config=NULL)
	{
		if (False === parent::initialize($ctx, $config)) return False;

		if (isset($config['request'])) $this->request =& $config['request'];
	}

	function handle()
	{
		if (!empty($this->request)) $this->handle_request($this->request);
	}

	function handle_request($data)
	{
		$status = NULL;

		$form_name = $this->form_name;
		$form_fields_sep = $this->form_fields_sep;
		$form_fields = $this->form_fields;
		$this->errors = array();
		$form_prefix = (empty($form_name)
										? ''
										: $form_name.$form_fields_sep);

		foreach ($form_fields as $k=>$v)
		{
			/* заполняем $item данными из формы или по умолчанию */
			if (is_numeric($k)) { $field = $v; $value = NULL; }
			else { $field = $k; $value = $v; }

			$form_field = $form_prefix.$field;

			// field*
			if (strpos($form_field, '*') !== false)
			{
				$re = '('.str_replace('*', ')'.$form_fields_sep.'(.*)', $form_field);
				$field = str_replace('*', '', $field);
				$value = array();
				foreach ($data as $kk=>$vv)
				{
					if (preg_match('#^'.$re.'$#', $kk, $matches))
					{
						$value[$matches[2]] = $vv;
					}
				}
			}
			else
			// field
			if (isset($data[$form_field])) 
			{
				$value = $data[$form_field];
			}
			$item[$field] = $value;

			/* проверям значение */
			$field_info = array(
				'form_name'=>$form_name, // имя формы
				'name'=>$field,			 // имя поля формы для проверки 
				'value'=>$value,			 // значение  
				#'new_value'=>NULL,		 // сюда чекер может кинуть новое значение
			);
			$e = NULL;
			if (True !== $this->_checkField($field_info, $e))
			{
				$this->errors[$field] = $e;
			}
			if (array_key_exists('new_value', $field_info)) 
				$item[$field] = $field_info['new_value'];
		}

		/* проверям форму целиком */
		$form_info = array(
			'form_name' => $form_name,
			'items' => $item, 
			#'new_items'=>NULL,
		);
		$e = array();
		if (True !== $this->_checkForm($form_info, $e))
		{
			$this->errors = array_merge($this->errors, $e);
		}

		if (array_key_exists('new_items', $form_info)) 
			$item = $form_info['new_items'];

		if (empty($this->errors))
		{
			$form_info = array(
				'form_name' => $form_name,
				'items' => $item, 
			);
			$status = $this->_successForm($form_info);
		}
		else
		{
			$form_info = array(
				'form_name' => $form_name,
				'items' => $item, 
				'errors' => $this->errors,
			);
			$status = $this->_errorForm($form_info);
		}
		return $status;
	}

	function _checkField(&$field_info, &$error)
	{
		$status = True;
		$method = $this->buildCheckFieldMethodName($field_info);
		if (method_exists($this, $method))
		{
			// $this->checkField()
			$old_value = $value = $field_info['value'];
			$status = $this->$method($value, $error); // может изменить $value
			if ($value !== $old_value) $field_info['new_value'] = $value;
		}
		return $status;
	}

	function _checkForm(&$form_info, &$errors)
	{
		$status = True;
		$method = $this->buildCheckFormMethodName($form_info);
		if (method_exists($this, $method))
		{
			// $this->checkForm()
			$old_value = $value = $form_info['items'];
			$status = $this->$method($value, $errors); // может изменить $value
			if ($value !== $old_value) $form_info['new_items'] = $value;
		}
		return $status;
	}

	function _successForm(&$form_info)
	{
		$status = True;
		$method = $this->buildSuccessFormMethodName($form_info);
		if (method_exists($this, $method))
		{
			// $this->onSuccess()
			$status = $this->$method($form_info['items']);
		}
		return $status;
	}

	function _errorForm(&$form_info)
	{
		$status = True;
		$method = $this->buildErrorFormMethodName($form_info);
		if (method_exists($this, $method))
		{
			// $this->onError()
			$status = $this->$method($form_info['items'], 
				$form_info['errors']);
		}
		return $status;
	}

	function buildCheckFieldMethodName($field_info)
	{
		return 'check'.$field_info['form_name'].$field_info['name'];
	}

	function buildCheckFormMethodName($field_info)
	{
		return 'check'.$field_info['form_name'];
	}

	function buildSuccessFormMethodName($field_info)
	{
		return 'on'.$field_info['form_name'].'success';
	}

	function buildErrorFormMethodName($field_info)
	{
		return 'on'.$field_info['form_name'].'error';
	}

}


?>
