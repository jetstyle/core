<?
$this->UseClass("models/DBModel");

class BasicGallery extends DBModel
{
	var $table = 'gallery';

	var $fields = array('id', 
		'topic_id',
		'title',
		'descr', 
		'_state', '_modified', '_created', '_order',
	); 

	var $fields_info = array(
		array( 'name' => 'title',			 'source' => 'title',				'lang' => NULL,),
	//	array( 'name' => 'title',			 'source' => 'eng_title',			'lang' => 'en',),

		array( 'name' => 'descr',			 'source' => 'descr',				'lang' => NULL,),
	//	array( 'name' => 'descr',			 'source' => 'eng_descr',			'lang' => 'en',),

	);

	var $order = array('_order');

	var $folder = 'tiles';
	var $img_filename = 'gallery';
	var $small_img_filename = 'gallery_small';
	var $big_img_filename = 'gallery_big';


	function &getFile($pattern, $item)
	{
		$fname = sprintf($pattern, $item['id']);
		$file = $this->rh->upload->getFile($fname);
		if ($file !== false) {
			list($width, $height, $type, $attr) = getimagesize($file->name_full);
			$file->width = $width;
			$file->height = $height;
			return $file;
		} else {
			return NULL;
		}
	}
	function load($where=NULL, $limit=NULL, $offset=NULL)
	{
		parent::load($where, $limit, $offset);
		foreach ($this->data as $k=>$v)
		{
			$this->data[$k]['img'] = 
				$this->getFile($this->getFilePath($this->img_filename).'_%d', $v);
			$this->data[$k]['big_img'] = 
				$this->getFile($this->getFilePath($this->big_img_filename).'_%d', $v);
			$this->data[$k]['small_img'] = 
				$this->getFile($this->getFilePath($this->small_img_filename).'_%d', $v);
		}
	}

	function getFilePath($filename)
	{
		return empty($this->folder)
			? $filename
			: $this->folder.'/'.$filename;
	}

}  

?>
