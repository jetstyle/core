<?php

class ImageText
{	
	protected $fontFile = '';
	protected $fontSize = 16;
	
	protected $imageFile = '';
	
	protected $text = 'button';
	
	protected $imgResource = null;
	
	protected $positionMode = 'absolute';
	
	protected $x = 0;
	protected $y = 0;
	
	/**
	 * red, green, blue
	 */
	protected $color = array(0, 0, 0);
		
	
	public function setFont($value)	
	{
		$this->fontFile = $value;
	}
	
	public function setImage($value)	
	{
		$this->imageFile = $value;
	}
	
	public function setText($value)	
	{
		$this->text = iconv('CP1251', 'UTF-8', $value);
	}
	
	public function setFontSize($value)	
	{
		$this->fontSize = $value;
	}
	
	public function setColor($value)
	{
		$this->color = $value;
	}
	
	public function setPosition($x, $y = null)
	{
		if (is_numeric($x) && is_numeric($y))
		{
			$this->x = $x;
			$this->y = $y;
		}
		else
		{
			$this->positionMode = $x;
		}
	}
	
	public function getTextDimensions()
	{		
		$dim = imagettfbbox ( $this->fontSize, 0, $this->fontFile, $this->text);
		$width = abs($dim[2] - $dim[0]);
		$height = abs($dim[1] - $dim[7]);
		
		return array($width, $height);
	}
	
	public function saveToFile($fileName)
	{
		if (null === $this->imgResource)
		{
			$this->generate();
		}
		
		$ext = pathinfo($fileName, PATHINFO_EXTENSION);
		
		ob_start();
		switch ($ext)
		{
			case 'png':
				header('Content-type: image/png');
				imagepng($this->imgResource, null, 2);
			break;

			case 'gif':
				header('Content-type: image/gif');
				imagegif($this->imgResource, null);
			break;

			case 'jpeg':
			case 'jpg':
			default:
				header('Content-type: image/jpeg');
				imagejpeg ($this->imgResource, null, 96);
			break;
		}
		$data = ob_get_contents();
		ob_end_clean();

		$fp = fopen($fileName, 'w');
		if($fp)	
		{
			fwrite($fp, $data);
			fclose($fp);
		}
		else
		{
			throw new JSException("Can't open file for write: ".$fileName);
		}
	}
	
	protected function generate()	
	{
		$this->imgResource = $this->createResourceFromImage($this->imageFile);
		
		$imgWidth = imagesx($this->imgResource);
		$imgHeight = imagesy($this->imgResource);
		
		$dim = imagettfbbox( $this->fontSize, 0, $this->fontFile, 'a');
		$letterHeight = $dim[1] - $dim[7];
		
		list($textWidth, $textHeight) = $this->getTextDimensions();

		if ($textWidth > $imgWidth || $textHeight > $imgHeight)
		{
			throw new JSException("Text bounds greater than image bounds");
		}
		
		switch ($this->positionMode)
		{
			case 'center':
				$x = $imgWidth / 2 - $textWidth / 2;
				$y = $imgHeight / 2 + $letterHeight / 2;
				break;
			
			default:
				$x = $this->x;
				$y = $this->y;
				break;
		}
		
		$fontColor = ImageColorAllocate($this->imgResource, $this->color[0], $this->color[1], $this->color[2]);
		ImageTTFText($this->imgResource, $this->fontSize, 0, $x, $y, $fontColor, $this->fontFile, $this->text);
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
//			imagealphablending($img, true);
			imagesavealpha($img, true);
		}

		return $img;
	}
}
?>