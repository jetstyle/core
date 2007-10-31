<?php
/*

  ����-���������
  * see http://in.jetstyle.ru/rocket/rocketforms

  ���� ���������, ����������� �����������.

  FormComponent_abstract( &$config )
      - $field -- $field->config instance-a ����  

  -------------------

  * model       : �����������. ������ �� ������
  * validator   : �����������. ������ ��������, ��� ��������� ������ ����
  * wrapper     : �����������. ������ ���������� �������
  * view        : �����������. ������ ���������� �������
  * interface   : �����������. �� ������� �������, ���������� ������ �� �����

  -------------------

  ��� ������������ � ����� ������� -- �� ������:

  // ������. �������� � ������� � ����������

  * Model_LoadFromArray( $a )
  * Model_ToArray( &$a ) 

  * Model_DbInsert( &$fields, &$values )
  * Model_DbUpdate( $data_id, &$fields, &$values )
  * Model_DbAfterInsert( $data_id )
  * Model_DbAfterUpdate( $data_id )
  * Model_DbDelete( $data_id )
  * Model_DbLoad( $db_row )

  * Model_SetDefault()

  * Model_ToSession( &$session_storage )
  * Model_FromSession( &$session_storage )

  * Model_GetDataValue()

  // ���������

  * Validate()

  // ������� (���������� � �����������)

  * Wrapper_Parse( $field_content )
  * View_Parse()

  // ��������� (������� � ��������� ������)
  * Interface_SafeDataValue( $data_value )
  * Interface_Parse()
  * Interface_PostToArray( $post_data )

  // ��������� � ����������
  * Event_Register() -- ����������� � �����. ����������� ����� ����� ������ ��� �����
  * LinkToField( &$field ) -- ����������� � ����. 
                              �������� ��������, ����� ���� ����������� ��������� ���������� �������

================================================================== v.0 (kuso@npj)
*/

class FormComponent_abstract
{
   var $default_config = array();
   var $model_data;
   var $valid = true;

   function FormComponent_abstract( &$config )
   {
     if (!empty($this->default_config))
       Form::StaticDefaults($this->default_config, $config);
   }

   // �������� � ����
   function LinkToField( &$field )
   {
     $this->field = &$field;
   }

   // ����������� � �����
   function Event_Register()
   {
     $this->field->rh->debug->Trace( "event_register for: { ".$this->field->name." } ");
     $this->field->form->hash[ $this->field->name ] = &$this->field;
   }

   // MODEL ==============================================================================
   // ����� �������� � "�������� ��-���������"
   function Model_SetDefault()
   { /* abstract */ }
   // ��������� �������� � ���� "�����" ��� "�����"
   function Model_SetDataValue($model_value)
   { /* abstract */ }
   // ������� �������� � ���� "�����" ��� "�����"
   function Model_GetDataValue()
   { /* abstract */ }
   // dbg purposes: dump
   function Model_Dump()
   { return $this->model_data; } // simpliest known form
   // ---- ������ ----
   function Model_ToSession( &$session_storage )
   { /* abstract */ }
   function Model_FromSession( &$session_storage )
   { /* abstract */ }
   // ---- ������ � ���������� � ������� ----
   function Model_LoadFromArray( $a )
   { /* abstract */ }
   function Model_ToArray( &$a )
   { /* abstract */ }
   // ---- ������ � �� ----
   function Model_DbLoad( $db_row )
   { /* abstract */ }
   function Model_DbInsert( &$fields, &$values )
   { /* abstract */ }
   function Model_DbUpdate( $data_id, &$fields, &$values )
   { /* abstract */ }
   function Model_DbAfterInsert( $data_id )
   { /* abstract */ }
   function Model_DbAfterUpdate( $data_id )
   { /* abstract */ }
   function Model_DbDelete( $data_id )
   { /* abstract */ }


   // VALIDATOR ==============================================================================
   // ���������
   // ��� ������� ������ �������� ��� ����� ����� ����������
   function Validate()
   {
     $this->valid = true;
     $this->validator_params = @$this->field->config["validator_params"];
     $this->validator_messages = array();
     // ������: ��� ������, ���� ���������?
     // ������: $this->_Invalidate( "empty", "���� ����������� ��� ����������" );
     return $this->valid;
   }
   // ���� ����� ����� �����, ����� �������������� ���� � ���� �����������.
   // $reason -- ���� ��� �����������, 
   function _Invalidate( $reason, $msg="there is no custom message" )
   {
     $this->valid=false;

		 // shumkov: ���� ������ ���������, ���� �������� � ������� ���. ��������.
		 //          �� ��������� ������� ������� � ���� - ���-�� ���������, ��� ���������
		 //          ������ EasyFormI18n � � ��� ������ �������� ����������. �������� �
		 //          ����������� EasyFormI18n �������� $this->form->i18n_prefix = $this->i18n_prefix;
		 //          � ����� ��� ���������.
		 //          � �� ������ ������ ��� EasyFormI18n ����� � ����, ���� ����� �������� From � EasyFrom
		 //          � ������ � ������������, ��� ������ ����� ����������.
     // kuso: my practice with always-messageset oriented programming shows
     //       that is a great burden for single-language sites which are common

     $value = $this->field->rh->tpl->msg->Get( 'Form:Validator/'.$reason );
     if (!empty($value) && $value != 'Form:Validator/'.$reason) $msg = $value;
     
     $this->validator_messages[$reason] = $msg;
   }


   // WRAPPER ===========================================================================
   // ���������� ������ ����
   function Wrapper_Parse( $field_content )
   { return ""; }

   // VIEW ==============================================================================
   // ������� readonly ��������
   function View_Parse( $plain_data = NULL )
   { return ""; }

   // INTERFACE ==============================================================================
   // ������ ���� �� "�������"
   function Interface_SafeDataValue( $data_value )
   { return ""; }
   // ������� ����� ����������
   function Interface_Parse()
   { 
     $this->field->tpl->Set( "field", "_".$this->field->name );
     $this->field->tpl->Set( "field_id", "id_".$this->form->name."_".$this->field->name );
     if (isset($this->field->config["interface_tpl_params"]) && is_array($this->field->config["interface_tpl_params"]))
     {
       foreach( $this->field->config["interface_tpl_params"] as $param=>$value )
         $this->field->tpl->Set("params_".$param, $value );
     }
     return "";
   }
   // �������������� �� ����� � ������ ��� �������� �������
   function Interface_PostToArray( $post_data )
   { return array(); }

// EOC{ FormComponent_abstract }
}  
   

?>