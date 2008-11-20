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
		$data = preg_replace_callback("/<ins>(.*?)?<\/ins>/si", array($this, "correctMarks"), $data);
		$data = preg_replace_callback("/<big>(.*?)?<\/big>/si", array($this, "correctNotes"), $data);
		$data = preg_replace_callback("/<img(.*?)?[\/]{0,1}>/si", array($this, "correctImages"), $data);
		$data = preg_replace_callback("/<a(.*?)?>(.*?)?<\/a?>/si", array($this, "correctFiles"), $data);

		$data = preg_replace_callback("/<table[^>]*?class=\"([\w\s]+)\"[^>]*?>(.*?)?<\/table>/si", array($this, "correctTables"), $data);

		return $data;
	}

	protected function correctQuotes($matches)
	{
		$r= array('text' => $matches[1]);
		$this->tpl->setRef('*', $r);
		return $this->tpl->parse('editor/quote.html');
	}

	protected function correctMarks($matches)
	{
		$r= array('text' => $matches[1]);
		$this->tpl->setRef('*', $r);
		return $this->tpl->parse('editor/mark.html');
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
		$this->table = array('class' => $matches[1]);
		preg_replace_callback("/<thead>(.*?)?<\/thead>/si",array(&$this, "correctTableHead"), $matches[2], -1, $theads);
		preg_replace_callback("/<tbody>(.*?)?<\/tbody>/si",array(&$this, "correctTableBody"), $matches[2], -1, $tbodies);

		if (!$theads && !$tbodies)
		{
			$this->correctTableBody(array($matches[2]));
		}

		$template = $matches['1'];
		$template = preg_replace('#\s#', '_', $template);
		$template = preg_replace('#[^\w_]#', '', $template);
		$template = trim($template);

		$this->tpl->set('table', $this->table);
		return $this->tpl->parse('editor/tables/'.$template);
	}

	protected function correctTableHead($matches)
	{
		$this->rows = array();
		preg_replace_callback("/<tr.*?>(.*?)?<\/tr>/si",array(&$this, "correctTableRows"), $matches[0]);

		if (count($this->rows) > 0)
		{
			$this->rows[0]['is_first'] = true;
			$this->rows[count($this->rows) - 1]['is_last'] = true;
			$this->table['head'] = $this->rows;
		}
	}

	protected function correctTableBody($matches)
	{
		$this->rows = array();
		preg_replace_callback("/<tr.*?>(.*?)?<\/tr>/si",array(&$this, "correctTableRows"), $matches[0]);

		if (count($this->rows) > 0)
		{
			$this->rows[0]['is_first'] = true;
			$this->rows[count($this->rows) - 1]['is_last'] = true;
			$this->table['body'] = $this->rows;
		}
	}

	protected function correctTableRows($matches)
	{
		$this->cells = array();
		preg_replace_callback("/<(td|th)(.*?)?>(.*?)?<\/\\1>/si", array(&$this, "correctTableCells"), $matches[1]);

		if (count($this->cells) > 0)
		{
			$this->cells[0]['is_first'] = true;
			$this->cells[count($this->cells) - 1]['is_last'] = true;
		}

		$this->rows[] = array('cells' => $this->cells);
	}

	protected function correctTableCells($matches)
	{
		// parse attributes
		$attributes = array();
		preg_match_all('/(.*?)="(.*?[^\\\])"/si', $matches[2], $paramsMatches);
		if(is_array($paramsMatches[2]) && !empty($paramsMatches[2]))
		{
			foreach($paramsMatches[1] AS $i => $r)
			{
				$attributes[trim($r)] = trim(str_replace('\"', '"', $paramsMatches[2][$i]));
			}
		}

		$this->cells[] = array(
			'attributes' => $attributes,
			'data' => $matches[3]
		);
	}

}
?>