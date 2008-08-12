<?php
/*
 ¬ таблице должны быть пол€:

 inserted datetime NOT NULL default '0000-00-00 00:00:00',
 year int(11) NOT NULL default '0',
 month int(11) NOT NULL default '0',

 ѕол€ желательно проиндексировать.
 «аполнение полей на совести обработчика формы.
 */

Finder::useClass('ListSimple');

class ListNews extends ListSimple
{
	protected $template = 'list_news.html';
	protected $template_calendar = 'list_news.html:calendar';

	protected $pages; //объект постраничной рубрикации

	protected $year = 0;
	protected $month = 0;

	public function __construct( &$config )
	{
		//упор€дочиваем список
		if(!$config->order_by)
		{
			$config->order_by = 'inserted DESC';
		}

		//по этапу
		parent::__construct( $config );

		$this->prefix = $config->getModuleName().'_tree_';
		$this->defineDate();
	}

	public function handle()
	{
		$rh =& $this->rh;
		$tpl =& $rh->tpl;

		//assign some
		$tpl->set('prefix', $this->prefix);

		//рендерим фильтр по датам
		//мес€цы
		//грузим признаки загруженности по мес€цам
		$M = array();
		$rs = $rh->db->execute("
	    	SELECT DISTINCT month
	    	FROM ??".$this->config->get('table_name')."
	    	WHERE year='".$this->year."' AND _state <= 1 ".($this->config->get('where') ? " AND ".$this->config->get('where') : "" )
		);
		while($row = $rh->db->getRow())
		{
			$M[ $row['month'] ] = true;
		}

		$MONTHES_NOMINATIVE = array("","€нварь","февраль","март","апрель","май","июнь","июль","август","сент€брь","окт€брь","но€брь","декабрь");

		for($i=1;$i<=12;$i++)
		{
			$month_options .= "<option value='$i' ".( $i==$this->month ? "selected='true'" : '' ).' '.( $M[$i] ? "style='background-color:#eeeeee'" : '' ).">".$MONTHES_NOMINATIVE[$i]."</option>";
		}

		$tpl->set( '_month_options', $month_options );

		//годы
		$rs = $rh->db->execute("
	    	SELECT DISTINCT year
	    	FROM ??".$this->config->get('table_name')."
	    	WHERE _state <= 1 ".($this->config->get('where') ? " AND ".$this->config->get('where') : "" ) . "
	    	ORDER BY year ASC
	    ");

		$year_options = '';
		if ($rs)
		{
			while ($r = $this->rh->db->getRow($rs))
			{
				$year_options .= "<option value='".$r['year']."' ".( $r['year'] == $this->year ? "selected='true'" : '' ).">".$r['year']."</option>";
			}
		}

		$tpl->set( '_year_options', $year_options );
		$tpl->Parse( $this->template_calendar, '__calendar' );

		//по этапу
		parent::handle();
	}

	public function load()
	{
		parent::load("year='".$this->year."' AND month='".$this->month."'");
	}

	protected function defineDate()
	{
		$rh =& $this->rh;

		$this->year = intval(RequestInfo::get('year'));
		$this->month = intval(RequestInfo::get('month'));

		if (!$this->year || !$this->month)
		{
			$rs = $rh->db->queryOne("SELECT id, year, month FROM ??".$this->config->get('table_name')." WHERE _state<=1 ".($this->config->where ? " AND ".$this->config->where : "" )." ORDER BY inserted DESC");
			if($rs['id'])
			{
				$this->year = $rs['year'];
				$this->month = $rs['month'];
			}
			else
			{
				$this->year = date('Y');
				$this->month = date('m');
			}
		}

		RequestInfo::set('year', $this->year);
		RequestInfo::set('month', $this->month);
	}
}

?>