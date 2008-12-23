<?php

/*
 Upload -- типизированная работа с файлами

 ---------

 * Upload ( &$rh, $dir="", $table_name='' ) -- конструктор
 - $rh -- ссылка на $rh
 - $dir -- директория, начиная с которой ищутся файлы
 - $table_name -- имя таблицы, в которой копятся данные о типах

 * _Current( $file_name, $ext ) -- заполняет объект файла для указанного файла и расширения
 - $file_name -- имя файла без расширения от начала корневой директории
 - $ext -- расширение файла

 * CheckExt($ext,$type) -- если для указанного типа не известен content-type, то дописывает указанный content-type
 - $ext -- расширение
 - $type -- content-type для указанного расширения

 * IsAllowed($ext) -- проверяет, разрешены ли операции с данным расширением через чёрные или белые списки

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

 * GZip($file_name,$type="") -- запаковывает указанный файл в архив
 - $file_name -- имя файла без расширения
 - $type -- если указан, то ищется файл только этого типа

 * _GZip($file_name) -- запаковывает указанный файл в архив
 имя фала формируется из имени оригинала с суффиксом .gz
 - $file_name -- абсолютное имя файла

 объект файла:
 $current->name_full -- абсолютное имя файла
 $current->name_short -- имя файла с расширением, без всяких директорий
 $current->ext -- расширение файла
 $current->format -- формат файла, аббривеатура, например "MsWord"
 $current->_format -- формат файла, типа content-type, например "application/msword"
 $current->size -- размер файла в килобайтах
 $current->link -- имя файла с расширением и корневой директорий

 =============================================================== v.2 (Zharik)

 */
class Upload
{

	private static $instance = null;

	public $TYPES = array(); // ext => [type,word]
	public $ALLOW = array(); // белый список расширений
	public $DENY = array(); // чёрный список расширений
	public $GRAPHICS = array('jpg', 'jpeg', 'gif', 'png', 'bmp');

	protected $dir;
	protected $current = false; //последний загруженный/выбранный файл
	protected $chmod = 0744; //какие права выставлять на загруженный файл
	protected $webDir = null;

	protected $DIRS_SWAPPED = array(); //для DirSwap(),  DirUnSwap();

	private function __construct()
	{
		if (Config::get('upload_ext'))
		{
			$exts = explode(",", Config::get('upload_ext'));

			if (!empty($exts))
			{
				foreach ($exts as $ext)
				{
					$ext = trim($ext);
					$this->TYPES[ $ext ] = array($ext,$ext);
					$this->ALLOW [$ext]  = $ext;
				}
			}
		}
	}

	public static function &getInstance()
	{
		 if (null === self::$instance)
		 {
		 	self::$instance = new self();
		 	self::$instance->setDir(Config::get('files_dir'));
		 }

		 return self::$instance;
	}

	public function setDir($dir)
	{
		$this->dir = $dir;
		$this->webDir = (Config::get('front_end_path') ? Config::get('front_end_path') : RequestInfo::$baseUrl).str_replace(Config::get('project_dir'), '', $this->dir);
	}

	public function getDir()
	{
		return $this->dir;
	}

	public function uploadFile($_file, $file_name, $is_full_name=false, $params = NULL )
	{
		if(!is_array($_file))
		{
			$_file = $_FILES[ $_file ];
		}

		$uploaded_file = $_file['tmp_name'];
		
		if(is_uploaded_file($uploaded_file) || ( file_exists($_file['tmp_name']) && is_file($_file['tmp_name']) ) )
		{
			$this->current = false;
			//клиентские данные
			$type = $_file['type'];
			$ext = explode(".",$_file['name']);
			$ext = strtolower($ext[ count($ext)-1 ]);
			//проверка на допуск
			
			if( !$this->isAllowed($ext) ) return false;
			//грузим

			$this->delFile($file_name);

			$file_name_ext = $file_name.".".$ext;
			$file_name_full = ( $is_full_name )? $file_name : $this->dir.$file_name_ext;

			$B = filesize($uploaded_file);

			if($params['filesize'])
			{
				$kill = false;
				$size = $this->parseSizeParam($params['filesize']);

				if($size[0] == '')
				{
					if($B != $size[1])
					{
						$kill = true;
					}
				}
				else
				{
					eval('$kill = ('.$size[1].$size[0].$B.');');
				}

				if($kill)
				{
					@unlink($uploaded_file);
					return false;
				}

			}

			// check directory
			$dirname = dirname($file_name_full);
			if (!is_dir($dirname))
			{
				if (!$this->createDir($dirname))
				{
					throw new Exception("Upload: can't create directory ".str_replace(Config::get('project_dir'), '', $dirname));
				}
			}
			elseif (!is_writable($dirname))
			{
				throw new Exception("Upload: directory ".str_replace(Config::get('project_dir'), '', $dirname)." is not writable");
			}


			if ($params['actions'] && is_array($params['actions']))
			{
				if(in_array($ext, $this->GRAPHICS))
				{
					$image = $this->createResourceFromImage($uploaded_file);
					foreach ($params['actions'] AS $key => $value)
					{
						switch ($key)
						{
							case 'crop':
								$this->createThumb($image, array('x' => $value[0], 'y' => $value[1]), true);
								$this->cropImage($image, array('x' => $value[0], 'y' => $value[1]), $value[2]);
							break;

							case 'resize':
								$this->createThumb($image, array('x' => $value[0], 'y' => $value[1]), false);
							break;

							case 'cropWithoutResize':
								$this->cropImage($image, array('x' => $value[0], 'y' => $value[1]), $value[2]);
							break;

							case 'mask':
								$this->applyMaskToImage($image, $value);
							break;

							case 'opacity':
								$this->makeImageOpacity($image, $value);
							break;
						}
					}

//					header('Content-type: image/png');
//					imagepng($image);
//					die();

					$this->saveImageFromResource($image, $ext, $file_name_full);
				}
				else
				{
					move_uploaded_file($uploaded_file,$file_name_full);
				}
			}
			else
			{
				if(in_array($ext, $this->GRAPHICS) && is_array($params['size']) && (strlen($params['size'][0]) > 0 && strlen($params['size'][1]) > 0))
				{
					$A = getimagesize($uploaded_file);

					$x = $this->parseSizeParam($params['size'][0]);
					$y = $this->parseSizeParam($params['size'][1]);

					if($x[0] == '' && $y[0] == '') // resize
					{
						$thumbnail = $this->createResourceFromImage($uploaded_file);

						$this->createThumb($thumbnail, array('x' => $x[1], 'y' => $y[1]), $params['crop'] ? true : false);

						if ($params['mask'])
						{
							$this->applyMaskToImage($thumbnail, $params['mask']);
						}

						if ($params['crop'])
						{
							$this->cropImage($thumbnail, array('x' => $x[1], 'y' => $y[1]), $params['crop']);
						}

						if ($params['blur'])
						{
							$this->unsharpMask($thumbnail, 20);
						}

	//					header('Content-type: image/png');
	//					imagepng($thumbnail);
	//					die();

						if ($params['opacity'])
						{
							$this->makeImageOpacity($thumbnail, $params['opacity']);
						}

						$this->saveImageFromResource($thumbnail, $ext, $file_name_full);
					}
					else
					{
						$x[0] = $x[0] == '=' ? '==' : $x[0];
						$y[0] = $y[0] == '=' ? '==' : $y[0];

						eval('$_x = ('.$A[0].$x[0].$x[1].');');
						eval('$_y = ('.$A[1].$y[0].$y[1].');');

						if($_x && $_y)
						{
							move_uploaded_file($uploaded_file,$file_name_full);
						}
						else
						{
							@unlink($uploaded_file);
							return false;
						}
					}
				}
				else
				{
					move_uploaded_file($uploaded_file,$file_name_full);
				}
			}

			if($params['to_flv'] && $ext != 'flv')
			{
				$this->convertToFlv($file_name_full, $this->dir.$file_name.".flv");
				@unlink($file_name_full);
			}

			chmod($file_name_full,$this->chmod);
			$this->_current($file_name, $ext);
			return $this->current;
		}
		else
		{
			return false;
		}
	}

	protected function _current($file_name, $ext)
	{
		$file_name_ext = $file_name.".".$ext;
		$file_name_full = $this->dir.$file_name_ext;
		$this->current['name_full'] = $file_name_full;
		$this->current['name_short'] = $file_name_ext;
		$this->current['ext'] = strtolower($ext);
		$this->current['format'] = ($this->TYPES[$ext][1] ? $this->TYPES[$ext][1] : strtolower($ext));
		$this->current['_format'] = $this->TYPES[$ext][0];
		$this->current['size'] = floor(100.0*@filesize($file_name_full)/1024)/100;
		$this->current['link'] = $this->webDir.$this->current['name_short'];
	}

	protected function isAllowed($ext)
	{
		if( (count($this->ALLOW) && !in_array($ext,$this->ALLOW))	|| (count($this->DENY) && in_array($ext,$this->DENY)) )
		{
			return false;
		}
		return true;
	}

	protected function parseSizeParam($val)
	{
		$pattern = '/(<|>|>=|<=|=|==|)(\d+)/';
		preg_match($pattern, $val, $matches);
		return array($matches[1], $matches[2]);
	}

	protected function convertToFlv($fn, $ft)
	{
		exec("ffmpeg -i " . $fn . " -ar 22050 -ab 32 -f flv -s 320x240 ".$ft);
	}

	protected function createDir($dirname)
	{
		if (@mkdir($dirname, 0775, true))
		{
			return true;
		}
		else
		{
			return false;
		}
	}

	public function getFile( $file_name, $is_full_name=false )
	{
		$this->current = false;
		//взять расширение из полного имени?
		if( $is_full_name && @file_exists($file_name) )
		{
			$path_info = pathinfo($file_name);
			$ext = $path_info['extension'];
			$file_name = basename($file_name,'.'.$ext);
		}
		//указано не полное имя - ищем расширение
		if($ext=='')
		{
			foreach($this->ALLOW as $ext)
			{
				if(@file_exists($this->dir.$file_name.'.'.$ext))
				{
					break;
				}
				else
				{
					$ext = '';
				}
			}
		}
		if($ext!='')
		{
			$this->_current($file_name,$ext);
			return $this->current;
		}
		return false;
	}

	public function delFile( $file_name,  $is_full_name=false  ){
		if( $is_full_name )
		{
			@unlink($file_name);
		}
		else
		{
			foreach($this->ALLOW as $ext)
			{
				$file_name_full = $this->dir.$file_name.".".$ext;
				if(@file_exists($file_name_full))
				{
					unlink($file_name_full);
				}
			}
		}
	}

	public function dirSwap($dir){
		$this->DIRS_SWAPPED[] = $this->dir;
		$this->dir = $dir;
	}

	public function dirUnSwap($all=false){
		if( count($this->DIRS_SWAPPED) )
		if( $all ){
			$this->dir = $this->DIRS_SWAPPED[0];
			$this->DIRS_SWAPPED = array();
		}else $this->dir = array_pop($this->DIRS_SWAPPED);
	}

	protected function createResourceFromImage($filename)
	{
		$size = getimagesize($filename);

		$img = null;

		if ($size[2]==2)
		{
			$img = imagecreatefromjpeg ($filename);
		}
		elseif ($size[2]==1)
		{
			$img = imagecreatefromgif ($filename);
		}
		elseif ($size[2]==3)
		{
			$img = imagecreatefrompng ($filename);
		}

		return $img;
	}

	protected function saveImageFromResource($resource, $type = 'jpeg', $filename)
	{
		switch ($type)
		{
			case 'png':
				imagepng($resource, $filename, 2);
			break;

			case 'gif':
				imagegif($resource, $filename);
			break;

			case 'jpeg':
			case 'jpg':
			default:
				imagejpeg ($resource, $filename, 96);
			break;
		}
	}


	// ###################################### ReSize Image ################################# //
	public function createThumb(&$img, $thumbSize, $byLowerSide = false)
	{
		$size = array(
			imagesx($img),
			imagesy($img),
		);


		if (($size[0] <= $thumbSize['x']) && ($size[1] <= $thumbSize['y']))
		{
			return;
		}


		$xratio = $size[0] / $thumbSize['x'];
		$yratio = $size[1] / $thumbSize['y'];

		if ($xratio > $yratio)
		{
			$ratio = $byLowerSide ? $yratio : $xratio;
		}
		else
		{
			$ratio = $byLowerSide ? $xratio : $yratio;
		}
		$newWidth = round($size[0] / $ratio);
		$newHeight = round($size[1] / $ratio);

		$thumbnail = imagecreatetruecolor ($newWidth, $newHeight);

		imagealphablending($thumbnail, false);
		imagesavealpha($thumbnail, true);
		$tColor = imagecolorallocatealpha($thumbnail, 255, 255, 255, 127);
		imagefilledrectangle($thumbnail, 0, 0, $newWidth, $newHeight, $tColor);

		imagecopyresampled ($thumbnail, $img, 0,0,0,0, $newWidth, $newHeight, $size[0], $size[1]);

		// создаем плашку с размерами миниатюры. потом в нее вписываем полученное изображение
//		if($pl)
//		{
//			$x = 0;
//			$y = 0;
//
//			if(imagesx($thumbnail) < $size['x'])
//			{
//				$x = round(($size['x'] - imagesx($thumbnail)) / 2);
//			}
//
//			if (imagesy($thumbnail) < $size['y'])
//			{
//				$y = round(($size['y'] - imagesy($thumbnail)) / 2);
//			}
//
//			$t = $thumbnail;
//			$thumbnail = imagecreatetruecolor ($size['x'], $size['y']);
//			$fg = ImageColorAllocate($thumbnail, 255, 255, 255);
//			imagefill($thumbnail, 0, 0, $fg);
//
//			imagecopy($thumbnail, $t, $x, $y, 0, 0, imagesx($t), imagesy($t));
//			imagedestroy($t);
//		}
		imagedestroy($img);
		$img = $thumbnail;
	}

	protected function cropImage(&$img, $size, $cropType)
	{
		$w = imagesx($img);
		$h = imagesy($img);

		$thumbnail = imagecreatetruecolor ($size['x'], $size['y']);

		imagealphablending($thumbnail, false);
		imagesavealpha($thumbnail, true);
		$tColor = imagecolorallocatealpha($thumbnail, 255, 255, 255, 127);
		imagefilledrectangle($thumbnail, 0, 0, $size['x'], $size['y'], $tColor);

		if ($cropType === 'center')
		{
			imagecopy($thumbnail, $img, 0, 0, round(($w - $size['x']) / 2), round(($h - $size['y']) / 2), $size['x'], $size['y']);
		}
		elseif ($cropType === 'bottom')
		{
			imagecopy($thumbnail, $img, 0, 0, ($w - $size['x']), ($h - $size['y']), $size['x'], $size['y']);
		}
		else
		{
			imagecopy($thumbnail, $img, 0, 0, 0, 0, $size['x'], $size['y']);
		}
		imagedestroy($img);
		$img = $thumbnail;
	}

	protected function applyMaskToImage(&$img, $maskFilename)
	{
		$sourceMask = $this->createResourceFromImage($maskFilename);

		$sourceMaskSize = array(
			'x' => imagesx($sourceMask),
			'y' => imagesy($sourceMask)
		);

		$maskSize = array(
			'x' => imagesx($img),
			'y' => imagesy($img),
		);

		$mask = imagecreatetruecolor($maskSize['x'], $maskSize['y']);
		imagecopyresampled ($mask, $sourceMask, 0,0,0,0, $maskSize['x'], $maskSize['y'], $sourceMaskSize['x'], $sourceMaskSize['y']);
		imageDestroy($sourceMask);

		for ($x = 0; $x < $maskSize['x']; $x++)
		{ // each row
			for ($y = 0; $y < $maskSize['y']; $y++)
			{ // each pixel
				$maskRGBColor = imagecolorsforindex($mask, imagecolorat($mask, $x, $y));
//				var_dump($maskRGBColor);
//				die();
				if ($maskRGBColor['red'] > 250 && $maskRGBColor['blue'] > 250 && $maskRGBColor['blue'] > 250)
				{
					$rgbColor = imagecolorsforindex($img, imagecolorat($img, $x, $y));
					$newColor = imagecolorallocatealpha ($img, $rgbColor['red'], $rgbColor['green'], $rgbColor['blue'], 126);
					imagesetpixel ($img, $x, $y, $newColor);
				}

				/*
				$rgbColor = imagecolorsforindex($img, imagecolorat($img, $x, $y));
				if ($rgbColor['alpha'] < 100)
				{
					$newColor = imagecolorallocatealpha ($img, $rgbColor['red'], $rgbColor['green'], $rgbColor['blue'], $opacity);
					imagesetpixel ($img, $x, $y, $newColor);
				}
				*/
			}
		}
	}

	/**
	*	$img - image resource
	* 	$opacity - from 0 to 100
	*/
	protected function makeImageOpacity(&$img, $opacity = 63)
	{
		$w = imagesx($img);
		$h = imagesy($img);

		$opacity = round($opacity / 100 * 127);

		for ($x = 0; $x < $w; $x++)
		{ // each row
			for ($y = 0; $y < $h; $y++)
			{ // each pixel
				$rgbColor = imagecolorsforindex($img, imagecolorat($img, $x, $y));
				if ($rgbColor['alpha'] < 100)
				{
					$newColor = imagecolorallocatealpha ($img, $rgbColor['red'], $rgbColor['green'], $rgbColor['blue'], $opacity);
					imagesetpixel ($img, $x, $y, $newColor);
				}
			}
		}
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

	public function unsharpMask(&$img, $amount = 100, $radius = .5, $threshold = 3)
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
