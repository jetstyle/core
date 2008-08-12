<?php
/*

  �������, ������� ������ ������, ����������� "��������� �������� �����������"

  PrincipalStorage( &$principal )
      - $principal       -- � ������ ���������� �������������

  -------------------

  // ������� (����������� �����)

  * &Factory( &$principal, $model_name ) -- ������� ��������� ������ PrincipalStorage_<model_name>
      - $principal  -- � ������ ���������� �������������
      - $model_name -- �������� ������ "���������"

  // �������� ����������� (���������� �������), ������������� ������:

  * LoadById( $id ) -- ��������� �� ��������� �������������� (��������������� ��� FKEY ��� �������)
      - $id -- �����-�������������
  * LoadByLogin( $login, $realm="" ) -- ��������� ������� ������ �� ������
      - $login -- ��� ����� �����
      - $realm -- �������������� ��������, ����������� ������ ���� ������������� ��, ��������, "�����"

  * SetStoredPassword( $user_data, $new_invariant ) -- �������� � �� ���������� ���������
      - $user_data      -- ������� ���������� (�� ���� ������ ����������, ��� �� ������, ��������, ��������� "user_id"
      - $new_invariant  -- ����� �������� ���� "stored_invariant"
      - true, if success

  * SetLoginDatetime( $user_data, $datetime="" ) -- �������� � �� ���� ���������� ������
      - $user_data      -- ������� ���������� (�� ���� ������ ����������, ��� �� ������, ��������, ��������� "user_id"
      - $datetime       -- �� ����� ����-����� ��������? ���� ������, �� ���� �������.
                           � ������� time()

  NB: ��� ������ ���������� ��������� ��� ���������� � $principal->data,
      ���� false -- ���� �������� �� �������.

================================================================== v.1 (kuso@npj)
*/

class PrincipalStorage
{
   function PrincipalStorage( &$principal )
   {
     $this->principal = &$principal;
     $this->rh = &$principal->rh;
   }

   function LoadById($id)
   { return false; }
   function LoadByLogin($login, $realm="")
   { return false; }

   function SetStoredPassword( $user_data, $new_invariant )
   { return true; }

   function SetLoginDatetime( $user_data, $datetime="" )
   {
     if ($datetime == "") $datetime = time();
     return $this->_SetLoginDatetime( $user_data, $datetime );
   }
   function _SetLoginDatetime( $user_data, $datetime )
   {
   }

   function &Factory( &$principal, $model_name )
   {
     $class_name = "PrincipalStorage_".$model_name;
     // find script or die
     $file_source = Finder::FindScript_( "classes/PrincipalModels", $class_name );

     // uplink
     include_once( $file_source );

     eval('$product = &new '.$class_name.'( $principal );');
     return $product;
   }

// EOC{ PrincipalStorage }
}


?>