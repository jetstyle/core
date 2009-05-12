<?php
/**
 * Command line router
 *
 * @author lunatic <lunatic@jetstyle.ru>
 */
class CommandLineRouter extends BasicRouter
{
	public function &find($criteria)
	{
		if (empty($criteria)) return null;
		if (isset($criteria['class'])) return $this->findByClass($criteria['class']);
		return null;
	}

	function findByClass($class)
	{
		if (!empty($class))
		{
			if (substr($class, -10) != 'Controller')
			{
				$class .= 'Controller';
			}
			
			if ($path = Finder::findScript("classes/controllers", $class))
			{
				$config = array (
					'class' => $class
				);
				include_once($path);
				return $this->buildController($config);
			}
		}
		return null;
	}
}
?>