<?php

include( $rh->findScript_('handlers','_start') );

//не авторизован?
if( !$prp->IsAuth() )
{
	$rh->redirect( $rh->url.'login' );
}

include ($rh->findScript_('modules', $rh->getVar('module') . '/files'));

$rh->UseClass('Upload');
$upload = & new Upload($rh, $this->upload_dir ? $rh->front_end->file_dir . $this->upload_dir . "/" : $rh->front_end->file_dir);
$web_upload_dir = $rh->front_end->path_rel . 'files/' . ($this->upload_dir ? $this->upload_dir . "/" : "");

$id = intval($rh->getVar('id'));

if(!$_POST['resize'])
{
	foreach($this->_FILES AS $v)
	{
		foreach($v AS $vv)
		{
			if($vv['original'])
			{
				if ($file = $upload->getFile(str_replace('*', $id, $vv['filename'])))
				{
					$rh->tpl->set('original_image', $web_upload_dir . $file->name_short);
				}
			}
			elseif($vv['show'])
			{
				$rh->tpl->set('thumb_x', $vv['size'][0]);
				$rh->tpl->set('thumb_y', $vv['size'][1]);
			}
		}
	}
}
else
{
	foreach($this->_FILES AS $v)
	{
		foreach($v AS $vv)
		{
			if($vv['original'])
			{
				if ($file = $upload->getFile(str_replace('*', $id, $vv['filename'])))
				{
					$filename = $file->name_full;
				}
				else
				{
					die();
				}
			}
			elseif($vv['show'])
			{
				if ($file = $upload->getFile(str_replace('*', $id, $vv['filename'])))
				{
					$resizedFilename = $file->name_full;
					$resizedFilenameShort = $file->name_short;
				}
			}
		}
	}
	$range = array (
		'tlx' => intval($_POST['tlx']), // top-left x
		'tly' => intval($_POST['tly']), // top-left y
		'width' => intval($_POST['rw']), // range width
		'height' => intval($_POST['rh']), // range height
	);

	$size = array(
		'width' => $_POST['w'],
		'height' => $_POST['h'],
	);
	
	$currentSize = GetImageSize($filename);
	if (!$currentSize)
	{
		die("Invalid image properties!");
	}
	
	$im = null;
	
	if ($currentSize[2] == 2)
	{
		$im = imagecreatefromjpeg($filename);
	}
	elseif ($currentSize[2] == 1)
	{
		$im = imagecreatefromgif($filename);
	}
	elseif ($currentSize[2] == 3)
	{
		$im = imagecreatefrompng($filename);
	}
	
	if(!$im)
	{
		die("Invalid image format!");
	}
	
	$thumbnail = imagecreatetruecolor($size['width'], $size['height']);
	imagecopyresampled($thumbnail, $im, 0, 0, $range['tlx'], $range['tly'], $size['width'], $size['height'], $range['width'], $range['height']);
	
	$upload->unsharpMask($thumbnail);

	ob_start();

	if ($currentSize[2] == 2)
	{
		imagejpeg($thumbnail);
	}
	elseif ($currentSize[2] == 1)
	{
		imagegif($thumbnail);
	}
	elseif ($currentSize[2] == 3)
	{
		imagepng($thumbnail);
	}
	
	$thumb['data'] = ob_get_contents();
	ob_end_clean();
	
	imagedestroy($thumbnail);
	
	$f = fopen($resizedFilename, 'w');
	fwrite($f, $thumb['data']);
	fclose($f);
	unset($thumb);
	
	$params = "{'id' : '".$id."', 'src' : '".$web_upload_dir.$resizedFilenameShort."', 'width' : '".$size['width']."', 'height' : '".$size['height']."'}";
	die($params);
}


$rh->tpl->set('standart_params', "{'url' : '".$rh->url.'resize'."', 'module' : '".$rh->getVar('module')."', 'id' : '".$id."'}");

$rh->tpl->parse( "resize.html", "html_body" );
include( $rh->findScript_('handlers','_finish') );
?>