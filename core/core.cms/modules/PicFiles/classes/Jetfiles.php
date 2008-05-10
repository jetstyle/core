<?php
$this->useClass('PopupObjects');
class Jetfiles extends PopupObjects
{
	protected $rubricsTable = "picfiles_topics";
	protected $table = "picfiles";
	
	protected function getFile($data)
	{
		$filename = $this->_FILES['file'][0]['filename'];
		
		if ($file = $this->upload->getFile(str_replace("*", $data['id'], $filename)))
		{
			$result = array(
				'title' => $data['title'],
				'src' => $this->rh->front_end->path_rel.'files/'.$this->upload_dir.'/'.$file->name_short,
				'size' => $file->size,
				'ext' => $file->ext,
			);
			return $result;
		}
		else
		{
			return false;
		}
	}
}

?>