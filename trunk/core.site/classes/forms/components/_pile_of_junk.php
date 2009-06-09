<?php
/*

  ����-���������
  * see http://in.jetstyle.ru/rocket/rocketforms

  ���� ���������, ���������� ������� ��.

  FormComponent__pile_of_junk( &$config )
      - $field -- $field->config instance-a ����  

  -------------------

  ��� ������������ � ����� �������:

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

class FormComponent__pile_of_junk extends FormComponent_abstract
{
   // MODEL ==============================================================================
   // ����� �������� � "�������� ��-���������"
   function Model_SetDefault()
   {
     $this->model_data = $this->field->config["model_default"];
   }
   // ������� �������� � ���� "�����" ��� "�����"
   function Model_GetDataValue()
   {
     return $this->model_data;
   }
   // dbg purposes: dump
   function Model_Dump()
   {
     return $this->model_data;
   }
   // ---- ������ ----
   function Model_ToSession( &$session_storage )
   {
     $session_storage[ $this->field->name ] = $this->model_data;
   }
   function Model_FromSession( &$session_storage )
   {
     $this->model_data = $session_storage[ $this->field->name ];
   }
   // ---- ������ � ���������� � ������� ----
   function Model_LoadFromArray( $a )
   {
     $this->model_data = $a[ $this->field->name ];
   }
   function Model_ToArray( &$a )
   {
     $a[ $this->field->name ] = $this->model_data;
   }
   // ---- ������ � �� ----
   function Model_DbLoad( $db_row )
   { 
     $this->model_data = $db_row[ $this->field->name ];
   }
   function Model_DbInsert( &$fields, &$values )
   {
     $fields[] = $this->field->name;
     $values[] = $this->model_data;
   }
   function Model_DbUpdate( $data_id, &$fields, &$values )
   {
     return $this->Model_DbInsert( &$fields, &$values );
   }
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
     $this->validator_messages[$reason] = $msg;
   }


   // WRAPPER ===========================================================================
   // ���������� ������ ����
   function Wrapper_Parse( $field_content )
   {
     // ���� ���� ������?
     $this->field->tpl->Set( "is_valid", $this->field->validator->valid );
     if (!$this->field->validator->valid)
     {
       $msgs = array();
       foreach( $this->field->validator->validator_messages as $msg=>$text )
        $msgs[] = array( "msg" => $msg, "text" => $text );
       $this->field->tpl->Loop( $msgs, $this->field->form->config["template_prefix"]."errors.html:List", "errors" );
     }

     // ������ ������
     $this->field->tpl->Set( "content",        $field_content  );
     $this->field->tpl->Set( "wrapper_title",  $this->field->config["wrapper_title"] );
     $this->field->tpl->Set( "wrapper_desc",   $this->field->config["wrapper_desc"]  );
     return $this->field->tpl->Parse( $this->field->form->config["template_prefix_wrappers"].
                                      $this->field->config["wrapper_tpl"] );
   }

   // VIEW ==============================================================================
   // ������� readonly ��������
   function View_Parse()
   {
     $data = $this->field->model->Model_GetDataValue();
     if ($this->field->config["view_tpl"])
     {
       $this->field->tpl->Set( "view_prefix",  $this->field->config["view_prefix"] );
       $this->field->tpl->Set( "view_postfix", $this->field->config["view_postfix"] );
       $this->field->tpl->Set( "view_data",    $data );
       $data = $this->field->tpl->Parse( $this->form->config["template_prefix_views"].
                                         $this->field->config["view_tpl"] );
     }
     else // ������� ��� ������
     {
       if ($this->field->config["view_prefix"])
         $data= $this->field->config["view_prefix"].$data;
       if ($this->field->config["view_postfix"])
         $data= $this->field->config["view_postfix"].$data;
     }
     return $data;
   }

   // INTERFACE ==============================================================================
   // ������ ���� �� "�������"
   function Interface_SafeDataValue( $data_value )
   {
     return htmlspecialchars($data_value);
   }
   // ������� ����� ����������
   function Interface_Parse()
   {
     $_data = $this->field->model->Model_GetDataValue();
     $data  = $this->field->interface->Interface_SafeDataValue($_data);

     $this->field->tpl->Set( "interface_data", $data );
     $this->field->tpl->Set( "field",          "_".$this->field->name );
     return $this->field->tpl->Parse( $this->field->form->config["template_prefix_interface"].
                                      $this->field->config["interface_tpl"] );
   }
   // �������������� �� ����� � ������ ��� �������� �������
   function Interface_PostToArray( $post_data )
   {
      return array(
                $this->field->name => rtrim($post_data["_".$this->field->name]),
                   );
   }

// EOC{ FormComponent__pile_of_junk }
}  
   

?>