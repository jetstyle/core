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
	protected $validTableClasses = array('w100_simple', 'w100_decorated', 'w100_decorated2');
	
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
		
		$data = preg_replace_callback("/<img([^>]*?)class=\"flash\"([^>]*?)[\/]{0,1}>/si", array($this, "correctFlash"), $data);

                $data = preg_replace("/<p>(<img([^>]*?)mode=\"\d+\"([^>]*?)[\/]{0,1}>)<\/p>/si", '$1', $data);
		$data = preg_replace_callback("/<img(([^>]*?)mode=\"\d+\"([^>]*?))[\/]{0,1}>/si", array($this, "correctImages"), $data);
                
                $data = preg_replace_callback("/<a(([^>]*?)mode=\"file\"([^>]*?))>(.*?)?<\/a>/si", array($this, "correctFiles"), $data);
		$data = preg_replace_callback("/<table([^>]*?class=\"[\w\s]+\"[^>]*?)>(.*?)?<\/table>/si", array($this, "correctTables"), $data);

		return $data;
	}

	protected function correctQuotes($matches)
	{
		$r= array('text' => $matches[1]);
		$this->tpl->setRef('*', $r);

        return $this->tpl->parse('content/blocks/quote.html');
	}

	protected function correctMarks($matches)
	{
		$r= array('text' => $matches[1]);
		$this->tpl->setRef('*', $r);

		return $this->tpl->parse('content/blocks/mark.html');
	}

	protected function correctNotes($matches)
	{
		$r= array('text' => $matches[1]);
		$this->tpl->setRef('*', $r);

		return $this->tpl->parse('content/blocks/note.html');
	}
	
	protected function correctFlash($matches)
	{
		$result = $matches[0];

		preg_match_all('/(.*?)=(")(|.*?[^\\\])\\2/si', $matches[1]." ".$matches[2], $paramsMatches);
		
		if (is_array($paramsMatches[3]) && !empty($paramsMatches[3]))
		{

			$params = array();
			foreach ($paramsMatches[1] AS $i => $r)
			{
				$params[trim($r)] = trim(str_replace('\"', '"', $paramsMatches[3][$i]));
			}

            if ($params['rel'])
            {
            				
//				$decoded = "".htmlspecialchars_decode($params["rel"], ENT_QUOTES);
				$decoded = "".urldecode($params["rel"]);
				if(substr($decoded,1, 6)=="object")
				{
				    $result = $decoded;
				}
            }
			if ($params['id'])
			{
				$length = strlen($params['id']);
				$part = 0;
				$flashParams = array();
				$previousLetter = '';
				$letter = '';

				
				for ($i = 0; $i < $length; $i++)
				{
					$letter = $params['id']{$i};
					
					if ($letter == ';')
					{
						if ($previousLetter == '\\')
						{
							$flashParams[$part] = substr($flashParams[$part], 0, -1);
						}
						else
						{
							$part++;
							$previousLetter = $letter;
							continue;
						}
					}

					$flashParams[$part] .= $letter;
					$previousLetter = $letter;
				}
			}
		
			if (count($flashParams) == 3)
			{
				$params['src'] = $flashParams[0];
				$params['width'] = intval($flashParams[1]);
				$params['height'] = intval($flashParams[2]);
				
				
				$this->tpl->setRef('*', $params);
				
			    $parts = explode(".", $params["src"]); 
			    
			    if ($parts[1]=="flv")
					$result = $this->tpl->parse('content/blocks/flash_video.html');
                else
					$result = $this->tpl->parse('content/blocks/flash.html');
			}
			else if (count($flashParams) == 2)
			{
			    //var_dump($result);die();
			    return $result;
			}
		}
		
		return $result;
	}
	
	protected function correctFiles($matches)
	{
		preg_match_all('/(.*?)=(")(|.*?[^\\\])\\2/si', $matches[1], $paramsMatches);

		if(is_array($paramsMatches[3]) && !empty($paramsMatches[3]))
		{
			$res = array();
			foreach($paramsMatches[1] AS $i => $r)
			{
				$res[trim($r)] = trim(str_replace('\"', '"', $paramsMatches[3][$i]));
			}

                        $res['fileparams'] = explode('|', $res['fileparams']);
                        $res['size'] = $res['fileparams'][0];
                        $res['ext'] = $res['fileparams'][1];
                        $res['title'] = $matches[4];
                        $this->tpl->setRef('*', $res);

                        return $this->tpl->parse('content/blocks/file.html');
		}
		return $matches[0];
	}

	protected function correctImages($matches)
	{
		preg_match_all('/(.*?)=(")(|.*?[^\\\])\\2/si', $matches[1], $paramsMatches);
		if (is_array($paramsMatches[3]) && !empty($paramsMatches[3]))
		{
			$res = array();
			foreach ($paramsMatches[1] AS $i => $r)
			{
				$res[trim($r)] = trim(str_replace('\"', '"', $paramsMatches[3][$i]));
			}

			$this->tpl->setRef('*', $res);
			switch($res['mode'])
			{
				case 1:
					return $this->tpl->parse('content/blocks/images.html:image_preview');
					break;

				case 2:
					return $this->tpl->parse('content/blocks/images.html:image_small');
					break;

				case 3:
					return $this->tpl->parse('content/blocks/images.html:image_big');
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
		//$this->table = array('class' => $matches[1]);
                $params = $this->splitParams($matches[1]);
                $this->table = $params;

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
		

                        
		if ( !in_array($template, $this->validTableClasses))
		{
		    $template = $this->validTableClasses[0];
		}
		
		$this->tpl->set('table', $this->table);
		return $this->tpl->parse('content/blocks/tables/'.$template);
	}

	protected function correctTableHead($matches)
	{
		$this->rows = array();
		preg_replace_callback("/<tr.*?>(.*?)?<\/tr>/si",array(&$this, "correctTableRows"), $matches[0]);

		if (count($this->rows) > 0)
		{
			$this->table['head'] = $this->rows;
		}
	}

	protected function correctTableBody($matches)
	{
		$this->rows = array();
		preg_replace_callback("/<tr.*?>(.*?)?<\/tr>/si",array(&$this, "correctTableRows"), $matches[0]);

		if (count($this->rows) > 0)
		{
			$this->table['body'] = $this->rows;
		}
	}

	protected function correctTableRows($matches)
	{
		$this->cells = array();
		preg_replace_callback("/<(td|th)(.*?)?>(.*?)?<\/\\1>/si", array(&$this, "correctTableCells"), $matches[1]);

		$this->rows[] = array('cells' => $this->cells);
	}

        
	protected function correctTableCells($matches)
	{
                $attributes = $this->splitParams($matches[2]);

		$this->cells[] = array(
			'attributes' => $attributes,
			'data' => $matches[3]
		);
	}
        
        protected function splitParams($allParams)
        {
            $attributes = array();
            preg_match_all('/(.*?)=(")(|.*?[^\\\])\\2/si', $allParams, $paramsMatches);
            if(is_array($paramsMatches[3]) && !empty($paramsMatches[3]))
            {
                    foreach($paramsMatches[1] AS $i => $r)
                    {
                            $attributes[trim($r)] = trim(str_replace('\"', '"', $paramsMatches[3][$i]));
                    }
            }
            return $attributes;
        }

}
?>
