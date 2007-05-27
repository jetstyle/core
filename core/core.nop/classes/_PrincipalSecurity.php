<?php
/*

  �������, ������� ������ ������, ����������� "�������� �������"

  PrincipalSecurity( &$principal )
      - $principal       -- � ������ ���������� �������������

  -------------------

  // ������� (����������� �����)

  * &Factory( &$principal, $model_name ) -- ������� ��������� ������ PrincipalSecurity_<model_name>
      - $principal  -- � ������ ���������� �������������
      - $model_name -- �������� ������ "�������� �������"

  // �������� ����������� (���������� �������), ����� ����������� ������:

  * OnRestore( &$user_data )  -- ���������� � ������, ����� "Identify" ��������������� ���������� �� ������.
                                 ������ ����� ���������/��������� ���� ������, ��� �� ����� ��� �����������
                                 �������������. � ������ ��� ������ �������� �� �����
  * OnLogin( &$user_data )    -- ����� ��������� ������ by "Login", �� ����������� � ������.
                                 ������ ����� ���������/��������� ���� ������, ��� �� ����� ��� �����������
                                 �������������, ��������� ����� ������� � ������
  * OnGuest( &$user_data )    -- ���������� � ������, ����� "Guest" �������� �������� �������.
                                 ������ ����� ���������/��������� ���� ������, ��� �� ����� ��� �����������
                                 �������������. � ������ ��� ������ �������� �� �����.
                                 ������, ��� ������� ���������� ������������ ��������� OnGuest ==> OnLogin,
                                 ��� � ��������� � "������"

    NB: $user_data -- �� ���� ������ ��������� ��, ��� �������� � $principal->data, 
                      ��� �� ��������� �� ������ ��� ����� �������� � ��.

  // �������� ����� ��� �����������:

  * Check( &$user_data, $params="" ) - ��� ������ $principal->Security 
      - $user_data -- �� ��, ��� � ����
      - $params    -- ���������, ���������� ���������� � ��� �����.

================================================================== v.1 (kuso@npj)
*/
define( "GRANTED", true );
define( "DENIED",  false );

class PrincipalSecurity
{
   function PrincipalSecurity( &$principal )
   {
     $this->principal = &$principal;
     $this->rh = &$principal->rh;
   }

   function OnRestore( &$user_data )
   { return true; }

   function OnLogin( &$user_data )
   { return true;  }

   function OnGuest( &$user_data )
   { return $this->OnLogin( $user_data );  }

   function Check( &$user_data, $params="" )  // denied by default;
   { return DENIED; }

   function &Factory( &$principal, $model_name )
   {
     $class_name = "PrincipalSecurity_".$model_name;
     // find script or die
     $file_source = $principal->rh->FindScript_( "classes/PrincipalModels", $class_name );

     // uplink
     include_once( $file_source );

     eval('$product = &new '.$class_name.'( $principal );');
     return $product; 
   }

// EOC{ PrincipalSecurity }
}


?>