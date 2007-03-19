<?php
	
/*
	Upload -- типизированная рбота с файлами
	
	---------
	
  * Upload ( &$rh, $dir="", $table_name='' ) -- конструктор
  	- $rh -- ссылка на $rh
  	- $dir -- директория, начиная с которой ищутся файлы
		- $table_name -- имя таблицы, в которой копятся данные о типах
	
  * UploadFile($input_name,$file_name,$is_full_name=false) -- обработак загрузки файла
  		возвращает объект текущего файла
  	- $input_name -- имя поля формы типа file, через которое загружали файл
  	- $file_name -- новое имя фалйа, без расширения
		- $is_full_name - если true, то сохраняет файл в точности под указанным именем $file_name
	
  * GetFile($file_name,$is_full_name=false) -- возвращает соответствующий объект файла
		- $is_full_name - если true, то ищет файл в точности с именем $file_name
	
  * DelFile($file_name,$is_full_name=false) -- то же, что и GetFile, только найденный файл удаляется
  	- $file_name -- имя файла без расширения 
		- $is_full_name - если true, то удаляет файл в точности с именем $file_name
	
	* IsAllowed($ext) -- проверяет, разрешены ли операции с данным расширением через чёрные или белые списки
	
	* _Current( $file_name, $ext ) -- заполняет объект файла для указанного файла и расширения
	  - $file_name -- имя файла без расширения от начала корневой директории
		- $ext -- расширение файла
	
	* _CheckExt($ext,$type) -- если для указанного типа не известен content-type, то дописывает указанный content-type
	  - $ext -- расширение
		- $type -- content-type для указанного расширения
	
	объект файла:
	$current->name -- имя файла без корневой дериктории и расширения
	$current->name_full -- абсолютное имя файла
	$current->name_short -- имя файла с расширением, без всяких директорий
	$current->ext -- расширение файла
	$current->format -- формат файла, аббривеатура, например "MsWord"
	$current->content_type -- content-type, например "application/msword"
	$current->size -- размер файла в килобайтах
	$current->link -- путь файла на сайте с расширением
	$current->href -- путь файла на сайте с расширением от корневой директории сайта

	В БД нужна следующая таблица:

CREATE TABLE [db_prefix]_upload (
  ext varchar(6) NOT NULL default '',
  type varchar(100) NOT NULL default '',
  title varchar(20) NOT NULL default ''
);
	
=============================================================== v.2 (Zharik)

*/
class Upload {

	var $rh;	
	var $dir;
	var $current = false; //последний загруженный/выбранный файл
	var $table_name; //имя таблицы, в которой хранить данные о типах
	var $chmod = 0744; //какие права выставлять на загруженный файл
	
	var $TYPES = array(); // ext => [type,word]
	var $ALLOW = array(); // белый список расширений
	var $DENY = array(); // чёрный список расширений
	var $DIRS_SWAPPED = array(); //для DirSwap(),  DirUnSwap();
 	
	function Upload(&$rh,$dir="",$table_name='',$path_link=''){
		$this->rh =& $rh;
		$this->dir = $dir;//with trailing '/'
		$this->path_link=$path_link ? $path_link : dirname($dir).'/';
		$this->table_name = $table_name ? $table_name : $rh->db_prefix.'upload';
		$this->chmod = 0744;
		//читаем базу знаний
    $RES = $rh->db->Query("SELECT * FROM ".$this->table_name);
    foreach( $RES as $r)
      $this->TYPES[ $r['ext'] ] = array($r['type'],$r['title']);
	}
	
	function _Current($file_name,$ext){
 		$file_name_ext = $file_name.".".$ext;
   	$file_name_full = $this->dir.$file_name_ext;
		$this->current->name = $file_name;
		$this->current->name_full = $file_name_full;
		$this->current->name_short = $file_name_ext;
		$this->current->ext = $ext;
		$this->current->format = $this->TYPES[$ext][1];
		$this->current->content_type = $this->TYPES[$ext][0];
		$this->current->size = (integer)(@filesize($file_name_full)/1024);
		$this->current->link = $this->path_link.$this->current->name_short;
		$this->current->href = $this->rh->base_url.$this->current->link;
	}
	
	function _CheckExt($ext,$type){
		if(!isset($this->TYPES[$ext])){
			$this->TYPES[$ext] = array( $type, $ext );
      $this->rh->db->query("INSERT INTO ".$this->table_name."(ext,type,title) VALUES('$ext','$type','$ext')");
		}
	}
	
	function IsAllowed($ext){
		if( count($this->ALLOW) && !in_array($ext,$this->ALLOW) 
				|| count($this->DENY) && in_array($ext,$this->DENY) )
			return false;
		return true;
	}
	
  function UploadFile( $field_name, $file_name, $is_full_name=false, $resize = NULL, $crop=false  )
  {

  	$uploaded_file = $_FILES[ $field_name ]['tmp_name'];
   
  	if(is_uploaded_file($uploaded_file))
    {
	  	$this->current = false;
			//клиентские данные
    	$type = $_FILES[ $field_name ]['type'];
			$ext = explode(".",$_FILES[ $field_name ]['name']);
			$ext = $ext[ count($ext)-1 ];
			//проверка на допуск
			if( !$this->IsAllowed($ext) ) return false;
			//какое имя файла использовать?
			if( $file_name=='' )
				$file_name = str_replace( '/', '__', basename($_FILES[ $field_name ]['name'],'.'.$ext) );
			//грузим
			$this->_CheckExt($ext,$type);
			//$this->DelFile($file_name);    			//if($del_prev) ...
			$file_name_ext = $file_name.".".$ext;
     	    $file_name_full = (( $is_full_name )? $file_name : $this->dir.$file_name_ext);
          
            

     	if(is_array($resize) && $resize[0] > 0 && $resize[1] > 0)	
        {

          	$img = $this->CreateThumb($uploaded_file, array('x' => $resize[0], 'y' => $resize[1]), 1, $crop);
          	if($img['error']) return false;
            
          	$file = fopen($file_name_full, 'w');
            
			fwrite($file, $img['data']);
			fclose($file);
        }
        else
        {
        	move_uploaded_file($uploaded_file,$file_name_full);
        }
		chmod($file_name_full,$this->chmod);
		$this->_Current($file_name,$ext);
		return $this->current;
	  }//else
       //   die('zxc');
  }
	
  function GetFile( $file_name, $is_full_name=false )
  {
		$this->current = false;
		//взять расширение из полного имени?
		if( $is_full_name && @file_exists($file_name) ){
			$path_info = pathinfo($file_name);
			$ext = $path_info['extension'];
			$file_name = basename($file_name,'.'.$ext);
		}
		//указано не полное имя - ищем расширение
		if($ext=='')
        {
			$A = array_keys($this->TYPES);
			foreach($A as $ext)
            {
//                if ($_GET['debug'])
//                    echo '<hr>'.$this->dir.$file_name.'.'.$ext;
				$t = $this->dir.$file_name.'.'.$ext;
				if(@file_exists($t))
				{
					break;
				}
				else $ext = '';
			}
		}
		if($ext!='')
        {
			$this->_Current($file_name,$ext);
			return $this->current;
		}
		return false;
  }
	
  function DelFile( $file_name,  $is_full_name=false  ){

	 if( $is_full_name ) @unlink($file_name);
		else {
			$A = array_keys($this->TYPES);
			foreach($A as $ext){
				$file_name_full = $this->dir.$file_name.".".$ext;
				
				if(@file_exists($file_name_full)) unlink($file_name_full);
			}
			
  	} 
  }
	
	function DirSwap($dir){
		$this->DIRS_SWAPPED[] = $this->dir;
		$this->dir = $dir;
	}
	
	function DirUnSwap($all=false){
		if( count($this->DIRS_SWAPPED) )
			if( $all ){
				$this->dir = $this->DIRS_SWAPPED[0];
				$this->DIRS_SWAPPED = array();
			}else	$this->dir = array_pop($this->DIRS_SWAPPED);
	}
	

// ###################################### ReSize Image ################################# //
function CreateThumb($filename, $thumb_size, $blur = 0, $crop=false)
{
	$size = GetImageSize($filename);

	if (!$size)
	{
		$thumb['error']="Invalid image properties!";
		return($thumb);
	}
	elseif (($size[0] <= $thumb_size['x']) && ($size[1] <= $thumb_size['y']))
	{
		$thumb['data']=file_get_contents($filename);
		return($thumb);
	}
    elseif ($size[2]==2)
    {
    	$im = imagecreatefromjpeg ($filename);
    }
    elseif ($size[2]==1)
    {
        $im = imagecreatefromgif ($filename);
    }
    elseif ($size[2]==3)
    {
        $im = imagecreatefrompng ($filename);
    }
	
	if (!$im)
	{
		$thumb['error']="Невозможно создать изображение.";
		return($thumb);
	}
/*	
	$xratio = $size[0] / $thumb_size['x'];
	$yratio = $size[1] / $thumb_size['y'];
	if ($xratio > $yratio)
	{
		$new_width = round($size[0] / $xratio);
		$new_height = round($size[1] / $xratio);
	}
	else
	{
		$new_width = round($size[0] / $yratio);
		$new_height = round($size[1] / $yratio);
	}

 	$thumbnail = imagecreatetruecolor ($new_width, $new_height);
 	imagecopyresampled ($thumbnail, $im, 0,0,0,0, $new_width, $new_height, $size[0], $size[1]);
*/
    if(!$crop) 
    {
        $xratio = $size[0] / $thumb_size['x'];
        $yratio = $size[1] / $thumb_size['y'];
        if ($xratio > $yratio)
        {
            $new_width = round($size[0] / $xratio);
            $new_height = round($size[1] / $xratio);
        }
        else
        {
            $new_width = round($size[0] / $yratio);
            $new_height = round($size[1] / $yratio);
        }

        $thumbnail = imagecreatetruecolor ($new_width, $new_height);
        imagecopyresampled ($thumbnail, $im, 0,0,0,0, $new_width, $new_height, $size[0], $size[1]);
    }
    else
    {
        $xratio = $size[0] / $thumb_size['x'];
        $yratio = $size[1] / $thumb_size['y'];
        if ($xratio < $yratio)
        {
            $new_width = round($size[0] / $xratio);
            $new_height = round($size[1] / $xratio);
        }
        else
        {
            $new_width = round($size[0] / $yratio);
            $new_height = round($size[1] / $yratio);
        }

        $t = imagecreatetruecolor ($new_width, $new_height);
        imagecopyresampled ($t, $im, 0,0,0,0, $new_width, $new_height, $size[0], $size[1]);

        $thumbnail = imagecreatetruecolor ($thumb_size['x'], $thumb_size['y']);
        imagecopy($thumbnail, $t, 0, 0, 0, 0, $thumb_size['x'], $thumb_size['y']);
    }

    imagedestroy($im);
	 
	if ($blur)
	{
		$this->UnsharpMask($thumbnail);
	}
	 
	ob_start();
	 
		if ($size[2]==2)
		{
			imagejpeg ($thumbnail);
		}
	  elseif ($size[2]==1)
	  {
   		imagegif($thumbnail);
  	}
  	elseif ($size[2]==3)
  	{
	   	imagepng($thumbnail);
	  }
		
		imagedestroy($thumbnail);
		$thumb['data'] = ob_get_contents();
	
	ob_end_clean();
	 
	return($thumb);
}


////////////////////////////////////////////////////////////////////////////////////////////////
////
////                  p h p U n s h a r p M a s k
////
////		Unsharp mask algorithm by Torstein Hшnsi 2003.
////		thoensi@netcom.no
////		Please leave this notice.
////
///////////////////////////////////////////////////////////////////////////////////////////////

function UnsharpMask(&$img, $amount = 100, $radius = .5, $threshold = 3)
{

	// $img is an image that is already created within php using
	// imgcreatetruecolor. No url! $img must be a truecolor image.

	// Attempt to calibrate the parameters to Photoshop:
	if ($amount > 500)
	{
		$amount = 500;
	}
	$amount = $amount * 0.016;
	if ($radius > 50)
	{
		$radius = 50;
	}
	$radius = $radius * 2;
	if ($threshold > 255)
	{
		$threshold = 255;
	}

	$radius = abs(round($radius)); 	// Only integers make sense.
	if ($radius == 0)
	{
		return true;
	}

	$w = imagesx($img);
	$h = imagesy($img);
	$imgCanvas = imagecreatetruecolor($w, $h);
	$imgCanvas2 = imagecreatetruecolor($w, $h);
	$imgBlur = imagecreatetruecolor($w, $h);
	$imgBlur2 = imagecreatetruecolor($w, $h);
	imagecopy ($imgCanvas, $img, 0, 0, 0, 0, $w, $h);
	imagecopy ($imgCanvas2, $img, 0, 0, 0, 0, $w, $h);


	// Gaussian blur matrix:
	//
	//	1	2	1
	//	2	4	2
	//	1	2	1
	//
	//////////////////////////////////////////////////

	// Move copies of the image around one pixel at the time and merge them with weight
	// according to the matrix. The same matrix is simply repeated for higher radii.
	for ($i = 0; $i < $radius; $i++)
	{
		imagecopy ($imgBlur, $imgCanvas, 0, 0, 1, 1, $w - 1, $h - 1); // up left
		imagecopymerge ($imgBlur, $imgCanvas, 1, 1, 0, 0, $w, $h, 50); // down right
		imagecopymerge ($imgBlur, $imgCanvas, 0, 1, 1, 0, $w - 1, $h, 33.33333); // down left
		imagecopymerge ($imgBlur, $imgCanvas, 1, 0, 0, 1, $w, $h - 1, 25); // up right
		imagecopymerge ($imgBlur, $imgCanvas, 0, 0, 1, 0, $w - 1, $h, 33.33333); // left
		imagecopymerge ($imgBlur, $imgCanvas, 1, 0, 0, 0, $w, $h, 25); // right
		imagecopymerge ($imgBlur, $imgCanvas, 0, 0, 0, 1, $w, $h - 1, 20 ); // up
		imagecopymerge ($imgBlur, $imgCanvas, 0, 1, 0, 0, $w, $h, 16.666667); // down
		imagecopymerge ($imgBlur, $imgCanvas, 0, 0, 0, 0, $w, $h, 50); // center
		imagecopy ($imgCanvas, $imgBlur, 0, 0, 0, 0, $w, $h);

		// During the loop above the blurred copy darkens, possibly due to a roundoff
		// error. Therefore the sharp picture has to go through the same loop to
		// produce a similar image for comparison. This is not a good thing, as processing
		// time increases heavily.
		imagecopy ($imgBlur2, $imgCanvas2, 0, 0, 0, 0, $w, $h);
		imagecopymerge ($imgBlur2, $imgCanvas2, 0, 0, 0, 0, $w, $h, 50);
		imagecopymerge ($imgBlur2, $imgCanvas2, 0, 0, 0, 0, $w, $h, 33.33333);
		imagecopymerge ($imgBlur2, $imgCanvas2, 0, 0, 0, 0, $w, $h, 25);
		imagecopymerge ($imgBlur2, $imgCanvas2, 0, 0, 0, 0, $w, $h, 33.33333);
		imagecopymerge ($imgBlur2, $imgCanvas2, 0, 0, 0, 0, $w, $h, 25);
		imagecopymerge ($imgBlur2, $imgCanvas2, 0, 0, 0, 0, $w, $h, 20 );
		imagecopymerge ($imgBlur2, $imgCanvas2, 0, 0, 0, 0, $w, $h, 16.666667);
		imagecopymerge ($imgBlur2, $imgCanvas2, 0, 0, 0, 0, $w, $h, 50);
		imagecopy ($imgCanvas2, $imgBlur2, 0, 0, 0, 0, $w, $h);
	}
	imagedestroy($imgBlur);
	imagedestroy($imgBlur2);

	// Calculate the difference between the blurred pixels and the original
	// and set the pixels
	for ($x = 0; $x < $w; $x++)
	{ // each row
		for ($y = 0; $y < $h; $y++)
		{ // each pixel

			$rgbOrig = ImageColorAt($imgCanvas2, $x, $y);
			$rOrig = (($rgbOrig >> 16) & 0xFF);
			$gOrig = (($rgbOrig >> 8) & 0xFF);
			$bOrig = ($rgbOrig & 0xFF);

			$rgbBlur = ImageColorAt($imgCanvas, $x, $y);

			$rBlur = (($rgbBlur >> 16) & 0xFF);
			$gBlur = (($rgbBlur >> 8) & 0xFF);
			$bBlur = ($rgbBlur & 0xFF);

			// When the masked pixels differ less from the original
			// than the threshold specifies, they are set to their original value.
			$rNew = (abs($rOrig - $rBlur) >= $threshold) ? max(0, min(255, ($amount * ($rOrig - $rBlur)) + $rOrig))	: $rOrig;
			$gNew = (abs($gOrig - $gBlur) >= $threshold) ? max(0, min(255, ($amount * ($gOrig - $gBlur)) + $gOrig))	: $gOrig;
			$bNew = (abs($bOrig - $bBlur) >= $threshold) ? max(0, min(255, ($amount * ($bOrig - $bBlur)) + $bOrig))	: $bOrig;

			$pixCol = imagecolorallocate ($img, $rNew, $gNew, $bNew);
			imagesetpixel ($img, $x, $y, $pixCol);
		}
	}
	imagedestroy($imgCanvas);
	imagedestroy($imgCanvas2);

	return true;
}
	
}

?>
