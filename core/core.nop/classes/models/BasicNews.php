<?
$this->UseClass("models/DBModel");

class BasicNews extends DBModel
{
	var $table = 'news';
	var $fields = array('id', 
		'title_pre', 'lead_pre', 
		'text_pre', 
		'day',
		'month',
		'year',
		'date',
		'date_supertag',
		/*
		"DATE_FORMAT(inserted, '%d') as `day`", 
		"DATE_FORMAT(inserted, '%m') as `month`", 
		"DATE_FORMAT(inserted, '%Y') as `year`", 
		"DATE_FORMAT(inserted, '%d.%m.%Y') as date", 
		"DATE_FORMAT(inserted, '/%d/%m/%Y/') as date_supertag",
		 */
	); 
	var $fields_info = array(
		array( 
			'name' => 'date',		 
			'source' => "concat(day, '.', month,'.', year)",
		),

		array( 
			'name' => 'date_supertag',		 
			'source' => "DATE_FORMAT(inserted, '/%d/%m/%Y/')", 
		),

		array( 
			'name' => 'years',		 
			'source' => "DISTINCT `year`",
			'alias' => 'year',
		),

		array( 'name' => 'title',			 'source' => 'title',				'lang' => NULL,),
		array( 'name' => 'title',			 'source' => 'eng_title',			'lang' => 'en',),

		array( 'name' => 'title_pre',		 'source' => 'title_pre',			'lang' => NULL,),
		array( 'name' => 'title_pre',		 'source' => 'eng_title_pre',		'lang' => 'en',),

		array( 'name' => 'lead',			 'source' => 'lead',					'lang' => NULL,),
		array( 'name' => 'lead',			 'source' => 'eng_lead',			'lang' => 'en',),

		array( 'name' => 'lead_pre',		 'source' => 'lead_pre',			'lang' => NULL,),
		array( 'name' => 'lead_pre',		 'source' => 'eng_lead_pre',		'lang' => 'en',),

		array( 'name' => 'text',			 'source' => 'text',					'lang' => NULL,),
		array( 'name' => 'text',			 'source' => 'eng_text',			'lang' => 'en',),

		array( 'name' => 'text_pre',		 'source' => 'text_pre',			'lang' => NULL,),
		array( 'name' => 'text_pre',		 'source' => 'eng_text_pre',		'lang' => 'en',),

	);

	var $where = '_state=0';
	var $order = 'inserted DESC';

	function loadYearsRange($where=NULL, $limit=NULL, $offset=NULL)
	{
		$this->fields = array("years");
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
