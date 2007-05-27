<?php
/* FormCalendar
*
* @author: lunatic
* @email:  lunatic@jetstyle.ru
* @last_modified: 15:29 08.02.2006
*
*/

/*
*	$this->CALENDAR_FIELDS = array ('public', 'actual');  -  ������ � ������, ��� ������� ����������� ���������
*
*	$this->YEAR = 'public';	 - ����, �� �������� ����� ������� ��� ��� ���������� � ����
*	$this->MONTH = 'public'; - ����, �� �������� ����� ������� ����� ��� ���������� � ����
*	$this->DAY = 'public';   - ����, �� �������� ����� ������� ���� ��� ���������� � ����
*/

$this->UseClass('FormFiles');

class FormCalendar extends FormFiles	{

	var $date_format = 'd.m.Y';												// ������ ����, ������������� �� ��������� (�.�. ����� ���� Id)
	var $r_mysql = '/(\d+)\-(\d+)\-(\d+) (\d+):(\d+):(\d+)/i';			// ������ ����, ���������� �� mysql
	var $r_date_out = '$3.$2.$1';											// �������������� ����, ���������� �� mysql
	var $r_time_out = '$4:$5';
	var $r_date_out_mysql = '$3-$2-$1';								// �������������� ����, ����������� � mysql

	var $r_date_in = '/(\d+)\.(\d+)\.(\d+)(.*)/i';		// ������ ����, ���������� �� �����

	var $r_year = '$3';																// ���
	var $r_month = '$2';															// �����
	var $r_day = '$1';																// ����

	function FormCalendar( &$config ){
		parent::FormFiles($config);
	}

	function Handle()	{
		$this->YEAR = $this->config->YEAR;
		$this->MONTH = $this->config->MONTH;
		$this->DAY = $this->config->DAY;
		$this->CALENDAR_FIELDS = $this->config->CALENDAR_FIELDS ? $this->config->CALENDAR_FIELDS : array();

		$this->Load();
		if( !$this->id )
		{
			foreach($this->CALENDAR_FIELDS AS $field)	
			{
				$this->rh->tpl->Assign('_'.$field, date($this->date_format));
				if($this->config->USE_TIME)
				{
					$this->rh->tpl->Assign('_'.$field.'_time', date('H:i'));
				}
			}
		} 
		else	
		{
			foreach($this->CALENDAR_FIELDS AS $field)	
			{
				if($this->config->USE_TIME)
				{
					$this->item[$field.'_time'] = preg_replace($this->r_mysql, $this->r_time_out, $this->item[$field]);
				}
				$this->item[$field] = preg_replace($this->r_mysql, $this->r_date_out, $this->item[$field]);
			}
		}

		//�� �����
		parent::Handle();
	}

	function Update(){

		$rh =& $this->rh;
		
		if($this->needUpdate())
		{
			if($this->YEAR)
			{
				$rh->GLOBALS[$this->prefix.'year'] = preg_replace($this->r_date_in, $this->r_year, $rh->GetVar($this->prefix.$this->YEAR));
				$this->UPDATE_FIELDS[] = 'year'; 
			}
			if($this->MONTH)
			{
				$rh->GLOBALS[$this->prefix.'month'] = preg_replace($this->r_date_in, $this->r_month, $rh->GetVar($this->prefix.$this->MONTH));
				$this->UPDATE_FIELDS[] = 'month'; 
			}
			if($this->DAY)
			{
				$rh->GLOBALS[$this->prefix.'day'] = preg_replace($this->r_date_in, $this->r_day, $rh->GetVar($this->prefix.$this->DAY));
				$this->UPDATE_FIELDS[] = 'day'; 
			}
			
			foreach($this->CALENDAR_FIELDS AS $field)	
			{
				if($this->config->USE_TIME)
				{
					$time = $rh->GetVar($this->prefix.$field.'_time').':00';
				}
				else
				{
					$time = date('H:i:s', time());
				}
				$rh->GLOBALS[$this->prefix.$field] =preg_replace($this->r_date_in, $this->r_date_out_mysql, $rh->GetVar($this->prefix.$field)).' '.$time; 
			}
		}
		
		return parent::Update();
	}

}


?>