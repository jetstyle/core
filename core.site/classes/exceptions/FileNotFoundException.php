<?php

class FileNotFoundException extends JSException
{
	protected $filename = '';
	
	public function setFilename($v)
	{
		$this->filename = $v;
	}
	
	public function getFilename()
	{
		return $this->filename;
	}
}
?>