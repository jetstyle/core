<?php
/**
 * Apply templates to editor objects (images, files, notes, quotes)
 * 
 * @author lunatic lunatic@jetstyle.ru
 * @created 25.05.2008 11:39
 * 
 */

class EditorObjectsCorrector
{
	protected $tpl = null;

	public function __construct()
	{
		$this->tpl = &Locator::get('tpl');
	}

	/**
	 * Correct text
	 *
	 * @param string $data
	 * @return string
	 */
	public function correct($data)
	{
		$data = preg_replace_callback("/<blockquote>(.*?)?<\/blockquote>/si", array($this, "correctQuotes"), $data);
		$data = preg_replace_callback("/<big>(.*?)?<\/big>/si", array($this, "correctNotes"), $data);
		$data = preg_replace_callback("/<img(.*?)?[\/]{0,1}>/si", array($this, "correctImages"), $data);
		$data = preg_replace_callback("/<a(.*?)?>(.*?)?<\/a?>/si", array($this, "correctFiles"), $data);
		/*
		 $data = preg_replace_callback("/<table.*?class=\"(.*?)?\".*?>(.*?)?<\/table>/si", array($this, "correctTables"), $data);
		 */

		return $data;
	}

	protected function correctQuotes($matches)
	{
		$r= array('text' => $matches[1]);
		$this->tpl->setRef('*', $r);
		return $this->tpl->parse('editor/quote.html');
	}

	protected function correctNotes($matches)
	{
		$r= array('text' => $matches[1]);
		$this->tpl->setRef('*', $r);
		return $this->tpl->parse('editor/note.html');
	}

	protected function correctFiles($matches)
	{
		preg_match_all('/(.*?)="(.*?[^\\\])"/si', $matches[1], $paramsMatches);
		if(is_array($paramsMatches[2]) && !empty($paramsMatches[2]))
		{
			$res = array();
			foreach($paramsMatches[1] AS $i => $r)
			{
				$res[trim($r)] = trim(str_replace('\"', '"', $paramsMatches[2][$i]));
			}
			
			if($res['mode'] == 'file')
			{
				$res['fileparams'] = explode('|', $res['fileparams']);
				$res['size'] = $res['fileparams'][0];
				$res['ext'] = $res['fileparams'][1];
				$res['title'] = $matches[2];
				$this->tpl->setRef('*', $res);

				return $this->tpl->parse('editor/file.html');
			}
			// link in new window
			elseif($res['target'] == '_blank')
			{
				//$res['_title'] = $match[2];
				//$this->tpl->setRef('*', $res);

				//return $this->tpl->Parse('typografica/link.html');
			}
				
		}
		return $matches[0];
	}

	protected function correctImages($matches)
	{		
		preg_match_all('/(.*?)="(.*?[^\\\])"/si', $matches[1], $paramsMatches);
		if (is_array($paramsMatches[1]) && !empty($paramsMatches[1]))
		{
			$res = array();
			foreach ($paramsMatches[1] AS $i => $r)
			{
				$res[trim($r)] = trim(str_replace('\"', '"', $paramsMatches[2][$i]));
			}

			$this->tpl->setRef('*', $res);
			switch($res['mode'])
			{
				case 1:
					return $this->tpl->Parse('editor/images.html:image_preview');
					break;
				
				case 2:
					return $this->tpl->Parse('editor/images.html:image_small');
					break;
				
				case 3:
					return $this->tpl->Parse('editor/images.html:image_big');
					break;
				
				default:
					return $matches[0];
				break;
			}
		}
		else
		{
			$matches[0];
		}
	}

	
	protected function correctTables($matches)
	{
		$this->rowsHead = 1;
		$this->row = 0;

		$matches[2] = preg_replace_callback("/<tr.*?>(.*?)?<\/tr>/si",array(&$this, "correctTableRows"),$matches[2]);

		return '<table class="'.$matches[1].'">'.$matches[2].'</table>';
	}

	protected function correctTableRows($matches)
	{
		$this->row++;
		$this->col = 0;

		$matches[1] = preg_replace_callback("/<td.*?>(.*?)?<\/td>/si",array(&$this, "correctTableCells"),$matches[1]);

		return '<tr>'.$matches[1].'</tr>';
	}

	protected function correctTableCells($matches)
	{
		$this->col++;

		preg_match_all('/colspan\=.(\d)./si', $matches[0], $m);
		$colspan = ' colspan="'.$m[1][0].'" ';

		preg_match_all('/rowspan\=.(\d)./si', $matches[0], $m);
		$rowspan = ' rowspan="'.$m[1][0].'" ';

		if ($this->row == 1 && $m[1][0] > $this->rowsHead)	
		{
			$this->rowsHead = $m[1][0];
		}

		if ($this->row <= $this->rowsHead) 
		{
			return '<th '.$colspan.$rowspan.'>'.$matches[1].'</th>';
		}
		else
		{
			return '<td '.$colspan.$rowspan.'>'.$matches[1].'</td>';
		}
	}
}
?>