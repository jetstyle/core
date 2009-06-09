<?php

class ColumnView
{
	var $data = array();
	
	public function __construct(&$tree)
	{
	    $this->data = &$tree;
	}

	public function split( $partsCount )
	{
		if (!$partsCount)
		    return array( $this->data );
		
		$tree = $this->data;
		foreach( $tree as &$child )
		{
		    $sumCount += $this->countChildren($child);
		}
		//echo $sumCount;
		
		
		//идеальная длина теплицы
		$partLength = ceil($sumCount / $partsCount);
		$parts = array();
		$curPartLength = 0;
		$curPartNum = 0;
		
		//по первому уровню
		foreach( $tree as &$child )
		{
			$inc = $child['successors_count'] ? $child['successors_count'] + 1 : 1;
			//ставим в текущую теплицу
			if ($partLength - $curPartLength >= $curPartLength + $inc - $partLength)
			{
			    $parts[ $curPartNum ][] = $child;
			    $curPartLength += $inc;
			}
			//в следущую
			else
			{	
				$parts[++$curPartNum  ][] = $child;
				$curPartLength = $inc;
			}
		}
		return $parts;
	}
		
	private function countChildren(&$node)
	{

		if ($node['children'])
		{
			
			$successorsCount = 0;
			foreach ($node['children'] as &$child)
			{
			    $successorsCount += $this->countChildren($child);
			}
			
			$node['successors_count'] = $successorsCount;
//			$node['title'] .= "   ". $successorsCount;
			return $successorsCount + 1;
		}
		else
		{
			$successorsCount = ($node['successors_count'] ? $node['successors_count'] : 1);
//			$node['title'] .= "   ". $successorsCount;
			return $successorsCount;
		}
	}
	
}
?>