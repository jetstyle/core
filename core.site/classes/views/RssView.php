<?php
/**
 * 
 * @uses: Locator::get("controller")
 *
 * Ex:
 * $rss=new RssView();
 * $rss->setModel( $aDBModel );    или $rss->setData( $array );
 * $rss->getHtml();
 */
class RssView
{
    private $data = array();
    
    private $model;
    
    private $limit = 20;
    
    public function setData($data)
    {
        $this->data=$data;    
    }
    
    public function setModel($model)
    {
        $this->model = $model;
    }

    public function getHtml()
    {
        if (empty($this->data))
        {
            $this->model->setLimit($this->limit);
            $this->model->registerObserver('row', array($this, 'onRssRow'));
		    $this->data = $this->model->load();
		}
		else
		{
		    foreach ($this->data as $i=>$data)
		    {
		        $this->data[$i] = onRssRow($null, $data);
		    }
		}
		
		Locator::get("tpl")->set('items', $this->data);

		$channel = array(
			'title' => $this->xmlQuote(Config::get('project_title')),
			'description' => $this->xmlQuote(Config::get('project_title').': '.$this->data['title']),
			'link' => RequestInfo::$baseFull.Locator::get("controller")->url_to().'/'.implode('/', Locator::get("controller")->getParams()),
			'selflink' => RequestInfo::$baseFull.Locator::get("controller")->url_to().'/'.implode('/', array_merge(Locator::get("controller")->getParams(), array('rss'))),
			'lastbuild' => $this->data[0]['inserted'],
		);
		
//Locator::get("controller")->url_to().'/'.implode('/', array_merge($this->controllerParams, array('rss'))),

		Locator::get("tpl")->set('channel', $channel);
        return Locator::get("tpl")->parse("rss.html");
    }

	public function onRssRow(&$model, &$row)
	{
		$row['title'] = $this->xmlQuote($row['title']);
		$row['inserted'] = $this->getGmtDate($row['inserted']);
		$row['link'] = RequestInfo::$baseFull.Locator::get("controller")->url_to('item', $row);

		$row['text'] = preg_replace_callback("/<a([^>]*?)>(.*?)?<\/a>/si", array($this, "correctLinks"), $row['text']);
		$row['text'] = preg_replace_callback("/<img([^>]*?)[\/]{0,1}>/si", array($this, "correctImages"), $row['text']);
	}

	protected function correctLinks($matches)
	{
		preg_match_all('/(.*?)=(")(|.*?[^\\\])\\2/si', $matches[1], $paramsMatches);

		$params = '';

		if(is_array($paramsMatches[3]) && !empty($paramsMatches[3]))
		{
			foreach($paramsMatches[1] AS $i => $r)
			{
				$r = trim($r);
				$v = $paramsMatches[3][$i];
				if ($r == 'href')
				{
					$v = trim($v);
					if (substr($v, 0, 1) == '/')
					{
						$v = 'http://'.$_SERVER["HTTP_HOST"].$v;
					}
				}

				$params .= $r.'="'.$v.'" ';
			}
		}
		return '<a '.$params.'>'.$matches[2].'</a>';
	}

	protected function correctImages($matches)
	{
		preg_match_all('/(.*?)=(")(|.*?[^\\\])\\2/si', $matches[1], $paramsMatches);
		$params = '';

		if(is_array($paramsMatches[3]) && !empty($paramsMatches[3]))
		{
			foreach($paramsMatches[1] AS $i => $r)
			{
				$r = trim($r);
				$v = $paramsMatches[3][$i];
				if ($r == 'src')
				{
					$v = trim($v);
					if (substr($v, 0, 1) == '/')
					{
						$v = 'http://'.$_SERVER["HTTP_HOST"].$v;
					}
				}
				$params .= $r.'="'.$v.'" ';
			}
		}
		return '<img '.$params.' />';
	}

	protected function xmlQuote($str)
	{
		$str = html_entity_decode($str, ENT_QUOTES, 'cp1251');
		$str = htmlspecialchars($str, ENT_QUOTES, 'cp1251');
		return $str;
	}

	protected function getGmtDate($date)
	{
		return gmdate('D, d M Y H:i:s ', strtotime($date)).'GMT';
	}
}


