<?php
/*

����-���������
* see http://in.jetstyle.ru/rocket/rocketforms

FormComponent_password( &$config )
- $field -- $field->config instance-a ����

-------------------

* validator   : ��������� �������� ������ � �� ��� ������� ������ ��������
"validator_params" => { "min" => 5, }
* interface   : ���������� ��� ���� ��� �����, ������������ �� ������ � md5()

-------------------

// ���������
* Validate()

// ��������� (������� � ��������� ������)
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
						$this->_Invalidate( "empty", "���� ����������� ��� ����������" );
					}
					else
					{
						$this->_Invalidate( "password_empty", "��������� ��� ����, ����������" );
					}
				}
				else
				{
					if ($this->post_value2 == "")
					{
						$this->_Invalidate( "password_empty", "��������� ��� ����, ����������" );
					}
				}
	
				if ($this->valid) // ���� �� ��� ������
				{
					if (isset($this->validator_params["min_length"]))
					{
						if (strlen($this->post_value1) < $this->validator_params["min_length"])
						{
							$this->_Invalidate( "string_short", "������� �������� ��������" );
						}
					}
					 
					if ($this->post_value1 != $this->post_value2)
					{
						$this->_Invalidate( "password_diff", "�������� ���� �������� �� ���������" );
					}
				}
			}
		}
		return $this->valid;
	}

	// MODEL ==============================================================================
	// ---- ������ � ���������� � ������� ----
	function Model_LoadFromArray( $a )
	{
		// ���� � ������� ��� ����� ����, ������ � �������� ��� �� ������� �� ����!
		if (!isset($a[ $this->name ])) return;
		else return parent::Model_LoadFromArray( $a );
	}
	
	function Model_DbUpdate( $data_id, &$fields, &$values )
	{
		if ($this->post_value1) return $this->Model_DbInsert( $fields, $values );
	}


	// INTERFACE ==============================================================================
	// ������ ���� �� "�������"
	// ������� ����� ����������
	function Interface_Parse()
	{
		// ������� �������� � ���� ���� �� �����!
		$result = parent::Interface_Parse();
		 
		return Locator::get('tpl')->parse( $this->form->config["template_prefix_interface"].$this->config["interface_tpl"] );
	}
	// �������������� �� ����� � ������ ��� �������� �������
	function Interface_PostToArray( $post_data )
	{
		// 1. �������� �� �
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
