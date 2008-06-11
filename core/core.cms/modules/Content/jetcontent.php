<?php

$rh = &$this->rh;

$rh->tpl->set('url_rel', $rh->ri->hrefPlus('', array()));
$rh->cm_mode = intval($rh->ri->get('cm'));

//$rh->modes['news'] = 1;

$_id = $rh->ri->get('id');

if($_id)
{
	$id = explode('-', $_id);
	$f = 'load'.ucfirst($id[0]);

	header("Content-type: text/xml; charset=utf-8");

	echo "<?xml version=\"1.0\"?>\n\n";
	
	if($_id == 'content-1')
	{
		echo "<tree id='0'>\n";
		$r = $rh->db->queryOne("SELECT id, title, _path FROM ??content WHERE _parent = 0 AND _state = 0");
			
		$r['title'] = iconv('cp1251', 'UTF-8', htmlentities($r['title'], ENT_QUOTES, 'cp1251'));
		$r['title_ins'] = iconv('cp1251', 'UTF-8', htmlentities($r['title_ins'], ENT_QUOTES, 'cp1251'));
		echo '<item text="'.$r['title'].'" id="'.$r['id'].'" child="1" open="1" hideAddChildButton="true" hideAddBrotherButton="true" hideDelButton="true"><userdata name="link">'.($rh->cm_mode ? '' : $rh->front_end->path_rel).$r['_path'].'</userdata><userdata name="title_ins">'.($r['title_ins'] ? $r['title_ins'] : $r['title'])."</userdata>\n";
	}
	else
	{
		echo "<tree id='".$_id ."'>\n";
	}
	

	if (function_exists($f))
	{
		echo $f($id[1], $rh);
	}

	if($_id == 'content-1')
	{
		echo "</item>\n";
	}

	echo "</tree>\n";
}
else
{
	//возврашаем шаблон
	if($rh->cm_mode)
	{
		$template = 'jetcontent.html:html_alt';
	}
	else
	{
		$template = 'jetcontent.html:html';		
	}
	echo $rh->tpl->parse($template);
}
die();


##################################################################################


function loadContent($id, &$rh)
{
	if($id > 0)
	{
		$sql = "
			SELECT id, mode
			FROM ??content 
			WHERE id = '".intval($id)."' AND _state IN(0,1)
		";
		$node = $rh->db->queryOne($sql);
	}
	else 
	{
		$node['mode'] = 0;
		$id = 0;
	}

	if($node)
	{
		if($node['mode'] && $rh->modes[$node['mode']])
		{
			$f = 'load'.ucfirst($node['mode']);
			if(function_exists($f))
			{
				return $f(0, $rh);
			}
			else 
			{
				return '';
			}
		}
		
		$sql = "
			SELECT c.id, c.title, c.mode, c._path, COUNT(cc.id) AS child, c._state
			FROM ??content AS c
			LEFT JOIN ??content AS cc ON (c.id = cc._parent AND cc._state IN(0,1))
			WHERE c._parent = '".intval($id)."'  AND c._state IN(0,1)
			GROUP BY c.id
			ORDER BY c._order ASC
		";

		$res = $rh->db->query($sql);
				
		$arr = array();
		if(is_array($res) && !empty($res))
		{
			foreach($res AS $r)
			{
				$arr[] = array(
					'id' => 'content-'.$r['id'],
					'title' => $r['title'].($r['_state'] ? ' [скрыт]' : ''),
					'title_ins' => $r['title'],
					'child' => (($r['child'] || $rh->modes[$r['mode']]) ? 1 : 0),
					'link' => ($rh->cm_mode ? '' : $rh->front_end->path_rel).$r['_path'],
				);
			}
		}

		return parseXml($arr);
	}
}

function loadNews($id, &$rh)
{
	$sql = "
		SELECT id, _path
		FROM ??content 
		WHERE _state = 0 AND mode = 'news'
	";
	$node = $rh->db->queryOne($sql);
	
	$arr = array();
		
	if($id == '0')
	{
		$sql = "
			SELECT DISTINCT year
			FROM ??news 
			WHERE _state = 0
			ORDER BY year ASC
		";
		$years = $rh->db->query($sql);
		
		if(is_array($years))
		{
			foreach($years AS $r)
			{
				$arr[] = array(
					'id' => 'news-year:'.$r['year'],
					'title' => $r['year'],
					'child' => 1,
					'link' => ($rh->cm_mode ? '' : $rh->front_end->path_rel).$node['_path'].'/'.$r['year'],
				);
			}
		}
	}
	else 
	{
		$id = explode(':', $id);
		if($id[0] == 'year')
		{
			$sql = "
				SELECT DISTINCT month
				FROM ??news 
				WHERE _state = 0 AND year = '".intval($id[1])."'
				ORDER BY month ASC
			";
			$month = $rh->db->query($sql);
			
			if(is_array($month))
			{
				foreach($month AS $r)
				{
					$arr[] = array(
						'id' => 'news-month:'.$r['month'].':'.$id[1],
						'title' => $r['month'],
						'child' => 1,
						'link' => ($rh->cm_mode ? '' : $rh->front_end->path_rel).$node['_path'].'/'.$id[1].'/'.$r['month'],
					);
				}
			}
		}
		elseif($id[0] == 'month')
		{
			$sql = "
				SELECT id, title, day, month, year 
				FROM ??news 
				WHERE _state = 0 AND year = '".intval($id[2])."' AND month = '".intval($id[1])."'
				ORDER BY inserted ASC
			";
			
			$res = $rh->db->query($sql);
			
			if(is_array($res))
			{
				foreach($res AS $r)
				{
					$arr[] = array(
						'id' => 'news-'.$r['id'],
						'title' => $r['title'],
						'child' => 0,
						'link' => ($rh->cm_mode ? '' : $rh->front_end->path_rel).$node['_path'].'/'.$r['year'].'/'.$r['month'].'/'.$r['day'].'/'.$r['id'],
					);
				}
			}		
		}
	}
	
	
	return parseXml($arr);
}

function parseXml($data)
{
	$out = '';
	if(is_array($data) && !empty($data))
	{
		foreach($data AS $r)
		{
			$r['title'] = iconv('cp1251', 'UTF-8', htmlentities($r['title'], ENT_QUOTES, 'cp1251'));
			$r['title_ins'] = iconv('cp1251', 'UTF-8', htmlentities($r['title_ins'], ENT_QUOTES, 'cp1251'));
			$out.= '<item text="'.$r['title'].'" id="'.$r['id'].'" child="'.$r['child'].'" hideAddChildButton="true" hideAddBrotherButton="true" hideDelButton="true"><userdata name="link">'.$r['link'].'</userdata><userdata name="title_ins">'.($r['title_ins'] ? $r['title_ins'] : $r['title'])."</userdata></item>\n";
		}
	}
	return $out;
}

?>