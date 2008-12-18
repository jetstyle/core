<?php
/**
 * Hcard
 * Prepare data for hcard parsing
 * 
 * @author lunatic lunatic@jetstyle.ru
 */

class Hcard
{
	private $data;
	private $resultData = array();
	
	public static function format($data)
	{
		$hcard = new Hcard($data);
		$hcard->prepare();
		return $hcard->getResultData();
	}
	
	private function __construct($data)
	{
		$this->data = $data;
	}
	
	public function prepare()
	{
		$this->constructName();
		$this->constructEmail();
		$this->constructPhone();
		$this->constructPhoto();
		$this->constructAddress();
		$this->constructIM();
		
		$this->makeVcard();
	}
	
	public function getResultData()
	{
		return $this->resultData;
	}
	
	private function constructName()
	{
		// formatted name
		$this->resultData['fn'] = array('type' => '', 'value' => '');
		
		// name parts (name, surname, patronymic)
		$this->resultData['n'] = array();
				
		// handle nickname
		if ($this->data['nickname'] && !$this->data['name'])
		{
			$this->resultData['fn']['type'] = 'nickname';
			$this->resultData['fn']['value'] = $this->data['nickname'];
			$this->resultData['nickname'] = $this->data['nickname'];
			return;
		}
		
		$this->resultData['nickname'] = $this->data['nickname'];
		
		if (!$this->data['name'] && $this->data['title'])
		{
			$this->data['name'] = $this->data['title'];
		}
		
		$namePartsCount = 0;
		
		$this->data['name'] = trim($this->data['name']);
		if (strpos($this->data['name'], ' ') !== false)
		{
			$nameParts = explode(' ', $this->data['name']);
			$this->data['name'] = $nameParts[0];
			if (!$this->data['surname'])
			{
				$this->data['surname'] = $nameParts[1];
			}
		}
		
		if ($this->data['name'])
		{
			$this->resultData['n']['name'] = $this->data['name'];
		}
		
		// handle company
		if ($this->data['company'] && (!$this->data['name'] || $this->data['company'] == $this->data['name']))
		{
			$this->resultData['n'] = array();
			$this->resultData['fn']['type'] = 'org';
			$this->resultData['fn']['value'] = $this->data['company'];
			$this->resultData['company'] = $this->data['company'];
			return;
		}
				
		if ($this->data['surname'])
		{	
			$this->resultData['n']['surname'] = $this->data['surname'];
		}
		
		if ($this->data['patronymic'])
		{
			$this->resultData['n']['patronymic'] = $this->data['patronymic'];
		}
	
	}
	
	private function constructEmail()
	{
		if ($this->checkmail($this->data['email']))
		{
			$this->resultData['email'] = $this->data['email'];
		}
	}
	
	private function constructPhone()
	{
		$this->resultData['tel'] = array();
		
		if ($this->data['phone'])
		{
			$this->resultData['tel'][] = array('type' => 'pref', 'value' => $this->data['phone']);
		}
		
		if ($this->data['fax'])
		{
			$this->resultData['tel'][] = array('type' => 'fax', 'value' => $this->data['fax']);
		}
	}
	
	private function constructPhoto()
	{
		$this->resultData['image'] = $this->data['image'];
	}
	
	private function constructAddress()
	{
		
	}
	
	private function constructIM()
	{
		$this->resultData['icq'] = $this->data['icq'];
		$this->resultData['jabber'] = $this->data['jabber'];
	}
	
	
	/**
	 * Create and cache vcard
	 *
	 */
	private function makeVcard()
	{
		$filename = md5(serialize($this->getResultData())).'.vcf';
		$vcardDir = Config::get('cache_dir').'/vcard';
		
		if (!file_exists($vcardDir.'/'.$filename))
		{
			if (!is_dir($vcardDir))
			{
				if (!mkdir($vcardDir, 0775, true))
				{
					return;
				}
			}
			
		    if (!$handle = fopen($vcardDir.'/'.$filename, 'w')) 
		    {
		    	return;
		    }
		
			$vcard = "BEGIN:VCARD\nVERSION:3.0\n";
			
			$vcard.= "N:".$this->resultData['n']['name'].";".$this->resultData['n']['surname'].";".$this->resultData['patronymic'].";;\n";

			if ($this->resultData['fn']['value'] && $this->resultData['fn']['type'] != 'org')
			{
				$vcard .= "FN:".$this->resultData['fn']['value']."\r";
			}
			
			if ($this->resultData['company'])
			{
				$vcard .= "ORG:".$this->resultData['company']."\n";
			}
			
			if (is_array($this->resultData['tel']))
			{
				foreach ($this->resultData['tel'] AS $tel)
				{
					$vcard .= "TEL;TYPE=".$tel['type'].":".$tel['value']."\n";
				}
			}
			
			if ($this->resultData['email'])
			{
				$vcard .= "EMAIL:".$this->resultData['email']."\n";
			}
			
			if ($this->resultData['icq'])
			{
				$vcard .= "X-ICQ:".$this->resultData['icq']."\n";
			}
			
			if ($this->resultData['jabber'])
			{
				$vcard .= "X-JABBER:".$this->resultData['jabber']."\n";
			}
			
			if ($this->resultData['image'])
			{
				$vcard .= "PHOTO;ENCODING=b;TYPE=".strtoupper($this->resultData['image']['ext']).":";
				$vcard .= base64_encode(file_get_contents($this->resultData['image']['name_full']));
				$vcard .= "\n";
			}
			
			$vcard .= "END:VCARD";
			
			fwrite($handle, iconv('cp1251', 'utf-16', $vcard));
		    fclose($handle); 
		}

		$this->resultData['vcard'] = RequestInfo::$baseUrl.'cache/'.Config::get('app_name').'/vcard/'.$filename;
	}
	
	private function pregtrim($str) 
	{
		return preg_replace("/[^\x20-\xFF]/","",@strval($str));
	}

	private function checkmail($email) 
	{
		// режем левые символы и крайние пробелы
		$mail=trim($this->pregtrim($email));
		// если пусто - выход
		if (strlen($mail)==0) return false;
		if (!preg_match("/^[\.a-z0-9_-]{1,20}@(([a-z0-9-]+\.)+(ru|tv|com|net|org|mil|edu|gov|arpa|info|biz|inc|name|[a-z]{2})|[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3})$/is",$mail))
		return false;

		return true;//$mail;
	}
}

?>