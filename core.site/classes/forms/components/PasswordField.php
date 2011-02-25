<?php
/*

Форм-процессор
* see http://in.jetstyle.ru/rocket/rocketforms

FormComponent_password( &$config )
- $field -- $field->config instance-a поля

-------------------

* validator   : проверяет парность пароля и не даёт вводить пустые значения
"validator_params" => { "min" => 5, }
* interface   : генерирует два поля для ввода, осуществляет их разбор и md5()

-------------------

// Валидатор
* Validate()

// Интерфейс (парсинг и обработка данных)
* Interface_SafeDataValue( $data_value )
* Interface_Parse()
* Interface_PostToArray( $post_data )

================================================================== v.0 (kuso@npj)
*/
Finder::useClass("forms/FormField");

class PasswordField extends FormField
{
	function Validate()
	{
		if (parent::validate())
		{
			if ($this->config["password_optional"] && $this->post_value1 == "" && $this->post_value2 == "")
			{
				unset($this->model_data);
				$this->valid = true;
			}
			else
			{
				if ($this->post_value1 == "")
				{
					if ($this->post_value2 == "")
					{
						$this->_Invalidate( "empty", "Поле обязательно для заполнения" );
					}
					else
					{
						$this->_Invalidate( "password_empty", "Заполните оба поля, пожалуйста" );
					}
				}
				else
				{
					if ($this->post_value2 == "")
					{
						$this->_Invalidate( "password_empty", "Заполните оба поля, пожалуйста" );
					}
				}
	
				if ($this->valid) // если всё ещё хорошо
				{
					if (isset($this->validator_params["min_length"]))
					{
						if (strlen($this->post_value1) < $this->validator_params["min_length"])
						{
							$this->_Invalidate( "string_short", "Слишком короткое значение" );
						}
					}
					 
					if ($this->post_value1 != $this->post_value2)
					{
						$this->_Invalidate( "password_diff", "Введённые вами значения не совпадают" );
					}
				}
			}
		}
		return $this->valid;
	}

	// MODEL ==============================================================================
	// ---- работа с хранилищем в массиве ----
	function Model_LoadFromArray( $a )
	{
		// если в массиве нет этого поля, значит и забирать его из массива не надо!
		if (!isset($a[ $this->name ])) return;
		else return parent::Model_LoadFromArray( $a );
	}
	
	function Model_DbUpdate( $data_id, &$fields, &$values )
	{
		if ($this->post_value1) return $this->Model_DbInsert( $fields, $values );
	}


	// INTERFACE ==============================================================================
	// защита поля от "клиента"
	// парсинг полей интерфейса
	function Interface_Parse()
	{
		// никаких значений у поля быть не может!
		$result = parent::Interface_Parse();
		 
		return Locator::get('tpl')->parse( $this->form->config["template_prefix_interface"].$this->config["interface_tpl"] );
	}
	// преобразование из поста в массив для загрузки моделью
	function Interface_PostToArray( $post_data )
	{
		// 1. получить из п
		$this->post_value1 = rtrim($post_data["_".$this->name."_1"]);
		$this->post_value2 = rtrim($post_data["_".$this->name."_2"]);

		if ($this->config["password_optional"] && $this->post_value1 == "")
			return array();
		else
			return array(
				$this->name => $this->post_value1
			);
	}

	// EOC{ FormComponent_password }
}
 

?>
