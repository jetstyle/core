<?php
/**
 * ContentRouter
 * 
 * @author lunatic <lunatic@jetstyle.ru>
 */

class ContentRouter extends BasicRouter
{
	public function &find($criteria)
	{
		if (empty($criteria)) return null;

		$content = DBModel::factory('Content');

		$where = array();
		if (!isset($criteria['class']) && $criteria['url']=="")
		{
			$where[] = "{mode}='home'";
		}
		elseif (isset($criteria['url']))
		{
			$where[] = '{_path} IN ('.DBModel::quote($this->getPossiblePaths($criteria['url'])). ')';
		}
		elseif (isset($criteria['class']))
		{
			$where[] = '{mode}='.DBModel::quote($this->getModeByClass($criteria['class']));
		}
		
		$where = implode(" AND ", $where);

		$data = $content->loadOne($where)->getArray();

		if (!empty($data))
		{
			$class = $this->getClassByMode($data['mode']);
			$config = array (
				'class' => $class,
				'data' => $data,
				'path' => $data['_path'],
				'url' => $criteria['url'],
			);
			if (Finder::findScript("classes/controllers", $class))
			{
				Finder::useClass("controllers/".$class);
				return $this->buildController($config);
			}
		}
		return null;
	}
	
	private function getClassByMode($mode)
	{
		return (($mode ? implode('', array_map(ucfirst, explode('_', $mode))) : "Content" ) .  "Controller");
	}

	private function getModeByClass($cls)
	{
		$res = strtolower(trim(preg_replace('#([A-Z])#', '_\\1', $cls), '_'));
		if ($res == 'content') $res = 0;
		return $res;
	}

	
}
?>