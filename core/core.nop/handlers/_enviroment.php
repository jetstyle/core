<?php
  //$rh должен быть инициализирован до инклюда
  $db =& $rh->db;
  $debug =& $rh->debug;
  $ri =& $rh->ri;

  if(!isset($tpl)) $tpl =& $rh->tpl; //скрипт может вызываться для построения шаблонных экшенов
  if (isset($tpl->msg)) $msg =& $tpl->msg;

  if (isset($rh->principal)) $principal = &$rh->principal;

?>