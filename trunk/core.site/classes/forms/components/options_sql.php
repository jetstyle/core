<?php
/*

  ����-���������
  * see http://in.jetstyle.ru/rocket/rocketforms

  FormComponent_options_sql( &$config )
      - $field -- $field->config instance-a ����

  -------------------

  * interface   : ������� �����. ����� �� ������ ����� select ��� radio.
  * view        : ������� �����. ������� �������� ��� ����� (�������� ��� � ������) �� �� �� �������
                  field->config["options_sql"] = "select ... as id, ... as name from table"

  -------------------

  // ��������� (������� � ��������� ������)

  * Interface_Parse()
  * Interface_PostToArray( $post_data )
  * View_Parse( $plain_data=NULL )

================================================================== v.0 (kuso@npj)
*/

Finder::UseClass("forms/components/options");

class FormComponent_options_sql extends FormComponent_options
{
   // ������� ������ ����� �� ��
   function _PrepareOptions()
   {
     $options = Locator::get('db')->query( $this->field->config["options_sql"] );
     $data = array();
     foreach( $options as $k=>$v ) $data[ $v["id"] ] = $v["name"];
     $this->field->config["options"] = isset($this->field->config["options"]) ? $this->field->config["options"] + $data : $data;
   }

   // INTERFACE ==============================================================================
   // ������� ����� ����������
   function Interface_Parse()
   {
     $this->_PrepareOptions();
     return parent::Interface_Parse();
   }
   // �������������� �� ����� � ������ ��� �������� �������
   function Interface_PostToArray( $post_data )
   {
     $this->_PrepareOptions();
     return parent::Interface_PostToArray( $post_data );
   }

   // VIEW ==============================================================================
   // ������� readonly ��������
   function View_Parse( $plain_data=NULL )
   {
     $this->_PrepareOptions();
     return parent::View_Parse( $plain_data );
   }


// EOC{ FormComponent_options_sql }
}


?>
