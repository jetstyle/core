<?php
/*
 * @created Feb 21, 2008
 * @author lunatic lunatic@jetstyle.ru
 * 
 * �������� � ������ ��������
 */

class ContentPageDomain extends BasicPageDomain
{

	function getPageClassByMode($mode)
	{
		return isset($this->rh->mode_map[$mode])
		? $this->rh->mode_map[$mode]
		: (($mode ? implode('', array_map(ucfirst, explode('_', $mode))) : "Content" ) .  "Page");
	}
	
	function getModeByPageClass($cls)
	{
		$res = strtolower(trim(preg_replace('#([A-Z])#', '_\\1', $cls), '_'));
		if ($res == 'content') $res = 0;
		return $res;
	}

	function &find($criteria=NULL)
	{
		if (empty($criteria)) return False; // FIXME: lucky@npj -- ������� ��� ��������?

		$content = DBModel::factory('Content');
		
		$where = array();
		if (!isset($criteria['class']) && $criteria['url']=="")
			$where[] = "mode='home'";
		elseif (isset($criteria['url']))
		{
			$where[] = '_path IN ('.$content->quote($this->getPossiblePaths($criteria['url'])). ')';
		}
		elseif (isset($criteria['class']))
			$where[] = 'mode='.$content->quote($this->getModeByPageClass($criteria['class']));
		$where = implode(" AND ", $where);

		$content->load($where);
		$data = $content[0];

		if (!empty($data))
		{
			$page_cls = $this->getPageClassByMode($data['mode']);
			$config = array (
			'class' => $page_cls,
			'config' => $data,
			'path' => $data['_path'],
			'url' => $criteria['url'],
			);
			if ($this->rh->FindScript("classes/controllers", $page_cls))
			{
				$this->rh->UseClass("controllers/".$page_cls);
				if ($this->handler = &$this->buildPage($config))
				{
					return True;
				}
			}
		}
		return False;
	}
}
?>