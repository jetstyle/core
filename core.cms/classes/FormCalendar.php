<?php
/* FormCalendar
*
* @author: lunatic
* @email:  lunatic@jetstyle.ru
* @modified: 15:29 08.02.2006
*
*/

/*
*	$this->CALENDAR_FIELDS = array ('public', 'actual');  -  ������ � ������, ��� ������� ����������� ���������
*
*	$this->YEAR = 'public';	 - ����, �� �������� ����� ������� ��� ��� ���������� � ����
*	$this->MONTH = 'public'; - ����, �� �������� ����� ������� ����� ��� ���������� � ����
*	$this->DAY = 'public';   - ����, �� �������� ����� ������� ���� ��� ���������� � ����
*
*	$this->USE_TIME - ������������ ���� + ����� (�� ��������� = true)
*/

Finder::useClass('FormIframe');

class FormCalendar extends FormIframe
{
	protected $date_format = 'd.m.Y';											// ������ ����, ������������� �� ��������� (�.�. ����� ���� Id)
	protected $r_mysql = '/(\d+)\-(\d+)\-(\d+) (\d+):(\d+):(\d+)/i';			// ������ ����, ���������� �� mysql
	protected $r_mysql_without_time = '/(\d+)\-(\d+)\-(\d+)/i';			// ������ ����, ���������� �� mysql
	protected $r_date_out = '$3.$2.$1';										// �������������� ����, ���������� �� mysql
	protected $r_time_out = '$4:$5';
	protected $r_date_out_mysql = '$3-$2-$1';								// �������������� ����, ����������� � mysql

	protected $r_date_in = '/(\d+)\.(\d+)\.(\d+)(.*)/i';		// ������ ����, ���������� �� �����

	protected $r_year = '$3';																// ���
	protected $r_month = '$2';															// �����
	protected $r_day = '$1';																// ����

	protected $USE_TIME = true;

	public function __construct( &$config )
	{
		parent::__construct($config);

		if($this->config['use_time'] === false)
		{
			$this['use_time'] = false;
		}
	}

	function handle()
	{
		$this->YEAR = $this->config['year'];
		$this->MONTH = $this->config['month'];
		$this->DAY = $this->config['day'];
		$this->CALENDAR_FIELDS = $this->config['calendar_fields'] ? $this->config['calendar_fields'] : array();

		$this->load();
		if( !$this->id )
		{
			foreach($this->CALENDAR_FIELDS AS $field)
			{
				$this->tpl->set('_'.$field, date($this->date_format));
				if($this->USE_TIME)
				{
					$this->tpl->set('_'.$field.'_time', date('H:i'));
				}
			}
		}
		else
		{
			foreach($this->CALENDAR_FIELDS AS $field)
			{
				if($this->USE_TIME)
				{
					$this->item[$field.'_time'] = preg_replace($this->r_mysql, $this->r_time_out, $this->item[$field]);
					$this->item[$field] = preg_replace($this->r_mysql, $this->r_date_out, $this->item[$field]);
				}
				else
				{
					$this->item[$field] = preg_replace($this->r_mysql_without_time, $this->r_date_out, $this->item[$field]);
				}
			}
		}

		//�� �����
		parent::handle();
	}

	function update()
	{
		if($this->YEAR)
		{
			$this->postData['year'] = preg_replace($this->r_date_in, $this->r_year, $_POST[$this->prefix.$this->YEAR]);
			$this->UPDATE_FIELDS[] = 'year';
		}
		if($this->MONTH)
		{
			$this->postData['month'] = preg_replace($this->r_date_in, $this->r_month, $_POST[$this->prefix.$this->MONTH]);
			$this->UPDATE_FIELDS[] = 'month';
		}
		if($this->DAY)
		{
			$this->postData['day'] = preg_replace($this->r_date_in, $this->r_day, $_POST[$this->prefix.$this->DAY]);
			$this->UPDATE_FIELDS[] = 'day';
		}

		foreach($this->CALENDAR_FIELDS AS $field)
		{
			if($this->USE_TIME)
			{
				if($time = $_POST[$this->prefix.$field.'_time'])
				{
					$time = $time.':00';
				}
				else
				{
					$time = date('H:i:s', time());
				}
				$date = preg_replace($this->r_date_in, $this->r_date_out_mysql, $_POST[$this->prefix.$field]).' '.$time;
			}
			else
			{
				$date = preg_replace($this->r_date_in, $this->r_date_out_mysql, $_POST[$this->prefix.$field]);
			}

			$this->postData[$field] = $date;
		}

		return parent::update();
	}

}

?>
