<?php
  //$rh ������ ���� ��������������� �� �������
  $db =& $rh->db;
  $debug =& $rh->debug;
  $ri =& $rh->ri;

  if(!isset($tpl)) $tpl =& $rh->tpl; //������ ����� ���������� ��� ���������� ��������� �������
  if (isset($tpl->msg)) $msg =& $tpl->msg;

  if (isset($rh->principal)) $principal = &$rh->principal;

?>