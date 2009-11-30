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

	protected function renderFields()
	{
		$renderResult = parent::renderFields();

		if ($renderResult && is_array($this->config['calendar']['fields']))
		{
			$item = &$this->getItem();
			if( !$item  || !$item['id'])
			{
				foreach($this->config['calendar']['fields'] AS $field)
				{
					$this->tpl->set('_'.$field, date($this->date_format));
					if($this->config['calendar']['use_time'] !== false)
					{
						$this->tpl->set('_'.$field.'_time', date('H:i'));
					}
				}
			}
			else
			{
				foreach($this->config['calendar']['fields'] AS $field)
				{
					if($this->config['calendar']['use_time'] !== false)
					{
						$item[$field.'_time'] = preg_replace($this->r_mysql, $this->r_time_out, $item[$field]);
						$item[$field] = preg_replace($this->r_mysql, $this->r_date_out, $item[$field]);
					}
					else
					{
						$item[$field] = preg_replace($this->r_mysql_without_time, $this->r_date_out, $item[$field]);
					}
				}
			}
		}
	}

	protected function constructPostData()
	{
		$postData = parent::constructPostData();

		if($this->config['calendar']['year'])
		{
			$postData['year'] = preg_replace($this->r_date_in, $this->r_year, $postData[$this->config['calendar']['year']]);
		}
		if($this->config['calendar']['month'])
		{
			$postData['month'] = preg_replace($this->r_date_in, $this->r_month, $postData[$this->config['calendar']['month']]);
		}
		if($this->config['calendar']['day'])
		{
			$postData['day'] = preg_replace($this->r_date_in, $this->r_day, $postData[$this->config['calendar']['day']]);
		}

		if (is_array($this->config['calendar']['fields']))
		{
			foreach($this->config['calendar']['fields'] AS $field)
			{
				if($this->config['calendar']['use_time'] !== false)
				{
					if($time = $_POST[$this->prefix.$field.'_time'])
					{
						$time = $time.':00';
					}
					else
					{
						$time = date('H:i:s', time());
					}
					$date = preg_replace($this->r_date_in, $this->r_date_out_mysql, $postData[$field]).' '.$time;
				}
				else
				{
					$date = preg_replace($this->r_date_in, $this->r_date_out_mysql, $postData[$field]);
				}

				$postData[$field] = $date;
			}
		}
		
		return $postData;
	}

}

?>