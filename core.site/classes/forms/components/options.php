<?php
/*

  ����-���������
  * see http://in.jetstyle.ru/rocket/rocketforms

  FormComponent_options( &$config )
      - $field -- $field->config instance-a ����  

  -------------------

  * interface   : ������� �����. ����� �� ������ ����� select ��� radio.
  * view        : ������� �����. ������� �������� ��� ����� �� field->config["options"], �������� ��� � ������.

  -------------------

  // ��������� (������� � ��������� ������)
  
  * Interface_Parse()
  * Interface_PostToArray( $post_data )
  * View_Parse( $plain_data=NULL )

================================================================== v.0 (kuso@npj)
*/

Finder::UseClass("forms/components/view_plain");

class FormComponent_options extends FormComponent_view_plain
{
   // INTERFACE ==============================================================================
   // ������� ����� ����������
   function Interface_Parse()
   {
     $data = $this->field->model->Model_GetDataValue();
     
     //������� ���������� - � ����������� �� ����
     $selected_mark = $this->field->config["options_mode"]=="radio" ? "checked=\"checked\"" : "selected=\"selected\"";
     
     //��������� ����� ��� �����������
     $A1 = $this->field->config["options"];
     $A2 = array();
     foreach($A1 as $v=>$t){
        $r["value"] = $v;
        $r["title"] = $t;
        $r["selected"] = $data==$v ? $selected_mark : "";
        $A2[] = $r;
     }
     
     //���������� ���������
     $result = FormComponent_abstract::Interface_Parse();
     Locator::get("tpl")->set("_options", $A2);
     return Locator::get("tpl")->parse( $this->field->form->config["template_prefix_interface"].
                                      $this->field->config["interface_tpl"] );
   }
   // �������������� �� ����� � ������ ��� �������� �������
   function Interface_PostToArray( $post_data )
   {
      return array(
                $this->field->name => rtrim(@$post_data["_".$this->field->name]), //IVAN
                   );
   }

   // VIEW ==============================================================================
   // ������� readonly ��������
   function View_Parse( $plain_data=NULL ){
     $data = $this->field->config["options"][ $plain_data!=NULL ? $plain_data : $this->field->model->Model_GetDataValue() ];
     return FormComponent_view_plain::View_Parse($data);
   }

   
// EOC{ FormComponent_options }
}  
   

?>
