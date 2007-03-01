<?php
/*
  ���������� �� ���������� ����:
  * ��������� ������ sql-��������
  * ������ ��������� �� sql-oriented violations

  DBAL( &$rh, $connect=true )

  ---------

  NB: ������ $rh ����� ���������� ����� ������, ���������� �������� $this->db_* 
      � ����������� $this->debug (������ ������ Debug)

  ���� ����� -- ������� ������� ���������� �� ����. 
  ��� ���������� ���� ���������� ������ ������� -- DBAL_****, ����� $rh->db_al="****"
  ����� ������ ������ � ����� ��������.

  ---------

  // ������ ���������� �������� ���������

  * Quote( $value ) -- ����������� ��������, ����� ��� ���������� ��� SQL
      - ���������� ���-�� ����� '13' ��� '������' 
        (������ � ����������� ���������, ������� ������ ��������� ��� �� ����)

  // ��������� ������� SQL � ��������� � ���� �����

  * Query( $sql, $limit=0, $offset=0 ) -- ����� ������������, ������������ ������ ������
      - ������ ������������� ������� (���� ������������ -- �����, �� "���������" �� ������� "active=1")
      - �������� ��������� �������
      - ����������� ��� � ������ �� ��������������� �������� (�����)
  * _Query( $sql, $limit=0, $offset=0 ) -- �� �� �����, ������ ��� �����������
  * QueryOne( $sql ) -- �� ��, ��� Query, �� ���� �� �� � ���������� ������ ������ ������ �����
                        ��� false, ���� ��������� -- �������.

  * Insert( $sql ) -- ������ ���� INSERT, ���������� ������������� ����������� ������

  // ������

  * RecordCount( $sql ) -- ����� �� �������� Query ��� ���� ������, ���������� �����
  * Close() -- ����� �� �������� � �����, � �� ���� �� ��� �� ��� "�������������" � ����� ������������ ������
  * _Connect() -- �������������� � ��, ������� ��������. ���������� �� ������������, ���� �� ������ ������������

  ---------

  ������� DBAL-��������� � $rh:

  $rh->db_host     = "localhost"
  $rh->db_user     = "username"
  $rh->db_password = "pwd"
  $rh->db_name     = "project_db"
  $rh->db_prefix   = "prefix_"

  $rh->db_al       = "mysql" <- �� ���� ����� �� �����, ����� ���� ��� ��� �����

  ---------

  �������� ��������������� � ����� ����� ������ �������� ��� ($db -- ��� ���������� ������):

  $a = $db->Query( "select * from ".$db->prefix." where active=1 and section_id=". $db->Quote(35) );
  echo $a[0]["section_id"];

=============================================================== v.2 (kuso@npj)
*/

class DBAL
{
  var $rh;        // use: $this->rh->debug->Trace (..->Error)
  var $lowlevel;

  /*
  // NB: �� ����������� � ������ ������ (ForR2, ?????)
  var $active_from = "active=1";        // �� ���� � �� ��� ���������� ������ "���������"
  var $active_to   = "active=active";   // ����-�� � �� ��� ����������������� ������������
  var $active_role = "editor";          // ��� ����� "����" ��� ������� ������ (���� ����� �� �������)
  */


  function DBAL( &$rh, $connect=true )
  {
    $this->rh = &$rh;
    $this->prefix = $rh->db_prefix;
    // ������� �������������� ����
    require_once( dirname(__FILE__)."/DBAL_".$rh->db_al.".php" );
    $lowlevel_name = "DBAL_".$rh->db_al;
    $lowlevel =& new $lowlevel_name( $this );
    $this->lowlevel = &$lowlevel;
    // connection, if any
    if ($connect) $this->_Connect();
  }

  function _Connect()
  { $this->lowlevel->Connect(); }

  function Close() 
  {  $this->lowlevel->Close(); }

  // ������ ���������� �������� ���������
  function Quote( $value ) 
  { return $this->lowlevel->Quote( $value ); }

  // ��������� ������� SQL � ��������� � ���� �����
  function Query( $sql, $limit=0, $offset=0 ) 
  {
    // #1. patch sql for active
    // �� ����������� � ������ ������ (ForR2, ?????)
    // #2. query
    return $this->_Query( $sql, $limit, $offset );
  }
  
  function _Query( $sql, $limit=0, $offset=0 ) 
  {
   $data = array();
   if ($r = $this->lowlevel->Query($sql, $limit, $offset))
   {
     while ($row = $this->lowlevel->FetchAssoc($r)) $data[] = $row;
     $this->lowlevel->FreeResult($r);
   }
   return $data;
  }

  function _Error( $error_msg )
  {
    $error_msg = "DBAL [".$this->rh->db_al."] Error: ".$error_msg;
    if ($this->rh->debug)
      $this->rh->Error($error_msg);
    else
    {
      ob_end_clean();
      die($error_msg);
    }

  }

  function QueryOne( $sql ) 
  {
    // #1. launch Query
    $res = $this->Query( $sql, 1 );
    // #2. get 1st
    if (sizeof($res)) return $res[0];
    else              return false;
  }

  function Insert( $sql )
  {
    $res = $this->lowlevel->Query($sql);
    return $this->lowlevel->InsertId();
  }

  // ������� ���-�� ������� � �������
  function RecordCount( $sql ) 
  {
    // ????? take a look on regexp pls.
    $sql = preg_replace( "/^select.*?((\s|\().*?(\s|\)))from/i", "select count(*) as _row_number from ", $sql );
    $res = $this->QueryOne($sql);
    if ($res) return $res["_row_number"];
    else return 0;
  }

// EOC{ DBAL } 
}


?>