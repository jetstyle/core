<?php

/**
 * Дерево. Вот такое /I\
 */
class Tree
{
	var $id = NULL;
	var $childs = array();
	var $parent = NULL;
	var $data = NULL;

	function Tree($tree=NULL)
	{
		if (isset($tree)) $tree->addChild($this);
	}

	function addChild(&$node)
	{
		if ($this->getChild($node->getId()) !== $node)
		{
			$id = count($this->childs);
			$this->childs[] =& $node;
			$node->setId($id);
		}
		$node->setParent($this);
	}

	function &getChild($id=0)
	{
		return $this->childs[$id];
	}
	function &getChilds()
	{
		return $this->childs;
	}

	function getId()
	{
		return $this->id;
	}
	function setId($id)
	{
		$this->id = $id;
	}

	function &getParent()
	{
		return $this->parent;
	}
	function setParent(&$node)
	{
		$this->parent =& $node;
		if ($node->getChild($this->getId()) !== $this)
		{
			$node->addChild($this);
		}
	}

	function setObject(&$data)
	{
		$this->data =& $data;
	}
	function &getObject()
	{
		return $this->data;
	}

}

class BasicTreeVisitor
{
	function beforeNode(&$node)
	{
	}
	function onNode(&$node)
	{
	}
	function afterNode(&$node)
	{
	}
}

class BasicTreeWalker
{

	function BasicTreeWalker(&$tree)
	{
		$this->factory =& $tree;
		$this->initialize();
	}

	function initialize()
	{
		return True;
	}

	function walk()
	{
	}

}

class DepthTreeWalker extends BasicTreeWalker
{

	function _walk(&$node, &$visitor)
	{
		if (False === $visitor->onNode($node)) return False;

		foreach ($node->getChilds as $k=>$v)
		{
			if (False === $visitor->beforeNode($node)) return False;
			if (False === $this->_walk($v, $visitor)) return False;
			if (False === $visitor->afterNode($node)) return False;
		}
	}

	function walk(&$visitor)
	{
		return $this->_walk($this->factory, $visitor);
	}

}

class WidthTreeWalker extends BasicTreeWalker
{

	function walk(&$visitor)
	{
		if (!isset($this->factory)) return True;

		$stack = array();

		if (False === $visitor->beforeNode($child)) return False;
		$stack[] =& $this->factory;
		$stack[] = NULL;
		$stack[] =& $this->factory;

		while (!empty($stack))
		{
			$node =& array_shift($this->stack);
			if (isset($node))
			{
				if (False === $visitor->onNode($node)) return False;
				$childs =& $node->getChild();
				if (!empty($childs))
				{
					foreach ($node->getChild() as $child) 
					{
						if (False === $visitor->beforeNode($child)) return False;
						$stack[] = $child;
					}
					$stack[] = Null;
					$stack[] =& $node;
				}
			}
			else
			{
				$node =& array_shift($this->stack);
				if (False === $visitor->afterNode($node)) return False;
			}
		}
	}

}

class TreeUtils
{

	function fromDB(&$data)
	{
		foreach ($data as $row)
		{
		}
	}

}

?>
