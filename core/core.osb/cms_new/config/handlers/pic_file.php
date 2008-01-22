<?
	$mode = $rh->GetVar('mode','integer');
	
	$TABLES = array( 
		'picfiles_lists'=>'picfile_lists_', 
		'picfiles'=>'picfile_', 
		'content_files'=>'content/file_'
	);
	
	$A = explode('/',$path->path_trail);

  //глюк проектировки
	if( $A[0]=="picfiles" && $mode==1 )
		$A[0] = "picfiles_lists";

	$table = isset($TABLES[$A[0]]) ? $A[0] : 'picfiles';
	$id = $A[1];
	
	$rh->UseClass('Upload',0);
	$upload =& new Upload($rh,'files/');
	$file = $upload->GetFile( $TABLES[$table].$id );
	
	if(!$file) $rh->EndError('Файл не найден.');
	
	//грузим название файла
	$rs = $db->QueryOne("SELECT title FROM ".$rh->project_name."_$table WHERE id='$id'");
	$title = $rs['title'];
	
	//чтоб не кэшировалось
  header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
  header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT"); 
  header("Cache-Control: no-store, no-cache, must-revalidate");
  header("Cache-Control: post-check=0, pre-check=0", false);
  header("Pragma: no-cache");
	
	//для нормального имени
	$safe = array( ' '=>'_' );
	
  //exel file
  header("Content-Disposition: attachment; filename=".strtr($title,$safe).'.'.$file->ext);
  header("Content-Type: ".$file->_format);
	
	//пуляем содержимое
	readfile($file->name_full);
	
?>