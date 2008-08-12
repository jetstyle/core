<?php
/*
 * @created Feb 21, 2008
 * @author lunatic lunatic@jetstyle.ru
 *
 * —траницы в дереве контента
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
		if (empty($criteria)) return False; // FIXME: lucky@npj -- вернуть все страницы?

		$content = DBModel::factory('Content');

		$where = array();
		if (!isset($criteria['class']) && $criteria['url']=="")
			$where[] = "mode='home'";
		elseif (isset($criteria['url']))
		{
			$where[] = '_path IN ('.DBModel::quote($this->getPossiblePaths($criteria['url'])). ')';
		}
		elseif (isset($criteria['class']))
			$where[] = 'mode='.DBModel::quote($this->getModeByPageClass($criteria['class']));
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
			if (Finder::findScript("classes/controllers", $page_cls))
			{
				Finder::useClass("controllers/".$page_cls);
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