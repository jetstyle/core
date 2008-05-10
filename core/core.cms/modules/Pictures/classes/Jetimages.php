<?php
$this->useClass('PopupObjects');
class Jetimages extends PopupObjects
{
	protected $rubricsTable = "pictures_topics";
	protected $table = "pictures";

	protected function getFile($data)
	{
		$filename = $this->_FILES['file'][0]['filename'];
		$previewFilename = $this->_FILES['file_small'][0]['filename'];
		
		if (($file = $this->upload->getFile(str_replace("*", $data['id'], $filename))) && ($filePreview = $this->upload->getFile(str_replace("*", $data['id'], $previewFilename))))
		{
			$size = getimagesize($file->name_full);
			$previewSize = getimagesize($filePreview->name_full);
			
			$result = array(
				'title' => $data['title'],
				'src' => array(
					'preview' => $this->rh->front_end->path_rel.'files/'.$this->upload_dir.'/'.$filePreview->name_short,
					'normal' => $this->rh->front_end->path_rel.'files/'.$this->upload_dir.'/'.$file->name_short,
				),
				'width' => array(
					'preview' => $previewSize[0],
					'normal' => $size[0],
				),
				'height' => array(
					'preview' => $previewSize[1],
					'normal' => $size[1],
				),
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