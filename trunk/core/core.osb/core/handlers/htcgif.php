<?
  
  //���������� ��������
//  header("Content-Type: image/gif");
//  header("Content-Disposition: inline;filename=z.gif");
//  echo base64_decode("R0lGODlhAQABAID/AMDAwAAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOx");
  
  //������������ ����
  $rh->UseLib('HTCron/htcron');
  htcCycle( $db, $rh->project_name."_htcron" );
  
?>