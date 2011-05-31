<?php
/*

  ����-���������
  * see http://in.jetstyle.ru/rocket/rocketforms

  FormComponent_model_plain( &$config )
      - $field -- $field->config instance-a ����  

  -------------------

  * model       : ������ ����-����� � ��

  -------------------

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

================================================================== v.0 (kuso@npj)
*/

class FormComponent_model_fk_select extends FormComponent_abstract
{
   // MODEL ==============================================================================
   // ����� �������� � "�������� ��-���������"
   function Model_SetDefault()
   {
     $this->model_data = isset($this->field->config["model_default"]) ? $this->field->config["model_default"] : "";

   }
   // ������� �������� � ���� "�����" ��� "�����"
   function Model_GetDataValue()
   {

     if ( empty($this->field->config["options"]) )
     {

        $model = DBModel::factory( $this->field->config["fk_model"] );
        ///$model->cleanUp();

        $opts = $model->load()->getArray();
        $options = $this->field->config["empty"] ? array() : array(0=>"�������");
        foreach ($opts as $opt){
            $options[$opt['id']] = $opt['title'];
        }

        $this->field->config["options"] = $options;
     }

     return isset($this->model_data["id"]) ? $this->model_data["id"] : $this->model_data;
   }
   // ��������� �������� � ���� "�����" ��� "�����"
   function Model_SetDataValue($model_value)
   { 
     $this->model_data = $model_value;
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

     // �������� �� ������� ���� � ������� (������ ���� "����" �� �����)
     if (isset($this->field->config["model_empty_from"]) && $this->model_data == "")
       $this->model_data = $this->field->form->hash[$this->field->config["model_empty_from"]]->
                                         model->Model_GetDataValue();

   }
   function Model_ToArray( &$a )
   {
     $a[ $this->field->name ] = $this->model_data;
   }
   // ---- ������ � �� ----
   function Model_DbLoad( $db_row )
   { 

     if(isset($db_row[ $this->field->name ]))
       $this->model_data = $db_row[ $this->field->name ];
     else
      $this->Model_SetDefault();

//var_dump($this->model_data  );die();
   }
   function Model_DbInsert( &$fields, &$values )
   {
     $fields[] = $this->field->name;
     $values[] = $this->model_data;
   }
   function Model_DbUpdate( $data_id, &$fields, &$values )
   {
     return $this->Model_DbInsert( $fields, $values );
   }



// EOC{ FormComponent_model_plain }
}  
   

?>
