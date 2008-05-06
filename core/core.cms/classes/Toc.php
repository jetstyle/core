<?php

class Toc
{

	var $rh;
	var $stack;
	var $curId = 0;
	var $numerate = false;
	var $limit = 0;
	var $nums = array();
	var $items = array();
	
	function toc( &$rh )
	{
		$this->rh = &$rh;
		$this->numerate = $rh->toc_numerate;
		$this->limit = $rh->toc_limit;
	}

	///////////////////////////////////////////////////////////////////////////////////////////////////////////
	///////////////////////////////////////////////////////////////////////////////////////////////////////////
	function correct( $data )
	{
		$data = preg_replace_callback("/<h([0-9])>(.*?)?<\/h\\1>/si", array(&$this, "callbackH"), $data);

		$out = $this->buildToc($this->childs[0]);
		if($out)
		{
			$this->rh->tpl->set('res', $out);
			$out = $this->rh->tpl->parse('toc.html:main');
			$data = $out.$data;
		}

		return $data;
	}
	///////////////////////////////////////////////////////////////////////////////////////////////////////////

	function callbackH($matches)	
	{			
		if($this->limit && $matches[1] > $this->limit)
		{
			return $matches[0];
		}
				
		$this->items[++$this->curId] = array('title' => trim(strip_tags($matches[2])), 'id' => $this->curId, 'level' => $matches[1]);
		$this->level[$matches[1]] = $this->curId;
		
		$this->childs[($this->level[$matches[1]-1] ? $this->level[$matches[1]-1] : 0)][] = $this->curId;
		
		if($this->numerate)
		{
			$nums = array();
			$nums[] = count($this->childs[0]);

			foreach($this->level AS $r)
			{
				if($num = count($this->childs[$r]))
				{
					$nums[] = $num;
				}
			}
			
			$this->items[$this->curId]['num'] = implode('.', $nums).".";
			$matches[2] = $this->items[$this->curId]['num'].' '.$matches[2];
		}
		
		return '<a name="mark'.$this->curId.'"></a>'.'<h'.$matches[1].'>'.$matches[2].'</h'.$matches[1].'>';
	}
	
	function buildToc($data)
	{
		$out = '';
		if(is_array($data) && !empty($data))
		{
			foreach($data AS $num => $id)
			{
				$r = $this->items[$id];
				
				$r['childs'] = $this->buildToc($this->childs[$id]);
				if($r['childs'])
				{
					$this->rh->tpl->setRef('res', $r['childs']);
					$r['childs'] = $this->rh->tpl->parse('toc.html:childs');
				}
							
				if($this->numerate)
				{
					$r['title'] = $r['num'].' '.$r['title'];
				}			
				
				$this->rh->tpl->setRef('*', $r);
				$out .= $this->rh->tpl->parse('toc.html:item');
			}
		}
		return $out;
	}
}
?>