<?
  $img = $path->path_trail;
  $title = urldecode($rh->GetVar("title"));
  
  //��������� ������ ����
  $reg_exp = "/\.{2,}|\:\/|\/{2,}|home\/|config\//";
  while( preg_match( $reg_exp, $img ) )
    $img = preg_replace( $reg_exp, '', $img );
  
  //�������� ����������
  $A = explode('.',$img);
  if(count($A)>1) array_pop($A);
  $_img = implode('',$A);
  
  $rh->UseClass('Upload',0);
  $upload =& new Upload($rh,'files/');
  $file = $upload->GetFile($_img);
  
  if(!$file) $rh->EndError('���� �� ������.');
  
  include( $rh->FindScript('handlers','_no_cache') );
  
  //������� ��� �����
  $title = str_replace('"','',$title);
  $title = preg_replace("/\s/",'_',$title);
  if($title)
    $title .= '.'.$file->ext;
  else
    $title = str_replace("/","_",$_img.'.'.$file->ext);
  
  //exel file
  header("Content-Disposition: attachment; filename=".$title);
  header("Content-Type: ".$file->_format);
  
  //������ ����������
  readfile($file->name_full);
  
?>