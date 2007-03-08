<?
$this->UseClass("models/DBModel");

class BasicNews extends DBModel
{
	var $table = 'news';
	var $fields = array('id', 
		'title_pre', 'lead_pre', 
		'text_pre', 
		"DATE_FORMAT(inserted, '%d') as `day`", 
		"DATE_FORMAT(inserted, '%m') as `month`", 
		"DATE_FORMAT(inserted, '%Y') as `year`", 
		"DATE_FORMAT(inserted, '%d.%m.%Y') as date", 
		"DATE_FORMAT(inserted, '/%d/%m/%Y/') as date_supertag",
	); 
	var $where = '_state=0';
	var $order = 'inserted DESC';

	function loadYearsRange($where=NULL, $limit=NULL, $offset=NULL)
	{
		$this->fields = array("DISTINCT `year`");
		$this->load($where, $limit, $offset);
	}
	function loadLast()
	{
		$this->limit = 6;
		$this->load();
		return $this->data;
	}

	/*
	 * lucky@npj: FIXME: наверно это не очено хорошая идя, что 
	 * данные сохраняются в разные переменные контейнера?
	 * или нет??
	 */
	function loadOne($id)
	{
		$where = " AND id=".$this->quote($id);
		$data  = $this->select($where);
		$this->item = $data[0];
	}

}  

?>
