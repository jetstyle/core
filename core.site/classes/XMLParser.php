<?php

class XMLParser
{
	const ITEM = 1;
	const ITEM_WITH_CHILDREN = 2;

	private $observers = array();
	private $source = null;
	private $pathStack = array();

	private $encoding = "UTF-8";

	private $observersStack = array(

	);

	private $path2observersCache = array();

	public function __construct()
	{

	}

	/**
	 *
	 * @param <string> $v
	 */
	public function setSource($v)
	{
		$this->source = $v;
	}

	public function getSource()
	{
		return $this->source;
	}

	public function setEncoding($v)
	{
		$this->encoding = $v;
	}

	public function getEncoding()
	{
		return $this->encoding;
	}

	public function registerObserver($key, $tag, $action, $type = self::ITEM)
	{
		if (isset($tag) && isset($action))
		{
			if (!is_array($this->observers[$tag]))
			{
				$regexp = $tag{0} == '/' ? '^' : '';
				$_tag = trim($tag, ' /');

				$patternParts = explode('//', $_tag);

				$regexp .= str_replace('/', '\/', $patternParts[0]);
				if ($patternParts[1])
				{
					$regexp .= '(\/\w+)*?\/'.$patternParts[1];
				}

				$this->observers[$tag] = array(
					'pattern' => '/'.$regexp.'$/i',
					self::ITEM => array(
					'stack' => array(),
					'actions' => array(),
					),
					self::ITEM_WITH_CHILDREN => array(
					'stack' => array(),
					'actions' => array(),
					),
				);
			}

			$this->observers[$tag][$type]['actions'][] = array(
				'action' => $action,
				'key' => $key,
			);
		}
	}

	public function parse()
	{
		$xml_parser = xml_parser_create($this->encoding);
		xml_set_element_handler($xml_parser, array(&$this, "startElement"), array(&$this, "endElement"));
		xml_set_character_data_handler($xml_parser, array(&$this, "elementContent"));
		if (!($fp = fopen($this->getSource(), "r")))
		{
			throw new JSException('Can\'t open xml file ('.$this->getSource().')');
		}

		while ($data = fread($fp, 4096))
		{
			if (!xml_parse($xml_parser, $data, feof($fp)))
			{
				throw new JSException(sprintf("XML error: %s at line %d", xml_error_string(xml_get_error_code($xml_parser)), xml_get_current_line_number($xml_parser)));
			}
		}
		xml_parser_free($xml_parser);
	}

	public function startElement($parser, $name, $attrs)
	{
		$name = strtolower($name);
		array_push($this->pathStack, $name);

		$path = '/'.implode('/', $this->pathStack);

		$tags = $this->getTags($path);

		$level = 0;
		for ($i = count($this->pathStack) - 1; $i >= 0; $i--)
		{
			if ($this->pathStack[$i] != $name)
			{
				break;
			}
			$level++;
		}

		if (count($tags))
		{
			array_push($this->observersStack, $tags);
		}

		if (!empty($this->observersStack))
		{
			foreach ($this->observersStack AS $_tags)
			{
				foreach ($_tags AS $tag)
				{
					if (!empty($this->observers[$tag][self::ITEM]['stack']))
					{
						while ($element = array_shift($this->observers[$tag][self::ITEM]['stack']))
						{
							foreach ($this->observers[$tag][self::ITEM]['actions'] AS $c)
							{
								call_user_func_array($c['action'], array($c['key'], $element));
							}
						}
					}

					if (!empty($this->observers[$tag][self::ITEM_WITH_CHILDREN]['stack']))
					{
						foreach ($this->observers[$tag][self::ITEM_WITH_CHILDREN]['stack'] AS $k => $element)
						{
							$_element = new XMLParserNode($name);
							$_element->setAttributes($attrs);
							$_element->setLevel($level);
							$element->addChild($_element);
							$this->observers[$tag][self::ITEM_WITH_CHILDREN]['stack'][$k] = $_element;
						}
					}
				}
			}
		}

		foreach ($tags AS $tag)
		{
			if (!empty($this->observers[$tag][self::ITEM]['actions']))
			{
				$element = new XMLParserNode($name);
				$element->setAttributes($attrs);
				$element->setLevel($level);
				$this->observers[$tag][self::ITEM]['stack'][] = $element;
			}

			if (!empty($this->observers[$tag][self::ITEM_WITH_CHILDREN]['actions']))
			{
				$element = new XMLParserNode($name);
				$element->setAttributes($attrs);
				$element->setLevel($level);
				$this->observers[$tag][self::ITEM_WITH_CHILDREN]['stack'][] = $element;
			}
		}

		$this->currentItemData = '';
	}

	public function endElement($parser, $name)
	{
		$path = '/'.implode('/', $this->pathStack);

		if (!empty($this->observersStack))
		{
			foreach ($this->observersStack AS $_tags)
			{
				foreach ($_tags AS $tag)
				{
					if (!empty($this->observers[$tag][self::ITEM_WITH_CHILDREN]['stack']))
					{
						foreach ($this->observers[$tag][self::ITEM_WITH_CHILDREN]['stack'] AS $k => $element)
						{
							$element->setContent($this->currentItemData);
							if ($element->getParent())
							{
								$this->observers[$tag][self::ITEM_WITH_CHILDREN]['stack'][$k] = $element->getParent();
							}
						}
					}
				}
			}
		}

		$tags = $this->getTags($path);

		if (count($tags))
		{
			array_pop($this->observersStack);
			foreach ($tags AS $tag)
			{
				$element = array_pop($this->observers[$tag][self::ITEM]['stack']);
				if ($element)
				{
					$element->setContent($this->currentItemData);

					foreach ($this->observers[$tag][self::ITEM]['actions'] AS $c)
					{
						call_user_func_array($c['action'], array($c['key'], $element));
					}
				}

				$element = array_pop($this->observers[$tag][self::ITEM_WITH_CHILDREN]['stack']);
				if ($element)
				{
					foreach ($this->observers[$tag][self::ITEM_WITH_CHILDREN]['actions'] AS $c)
					{
						call_user_func_array($c['action'], array($c['key'], $element));
					}
				}
			}
		}

		$this->currentItemData = '';
		array_pop($this->pathStack);
	}

	public function elementContent($parser, $data)
	{
		$this->currentItemData .= $data;
	}

	protected function getTags($path)
	{
		if (!array_key_exists($path, $this->path2observersCache))
		{
			$result = array();
			foreach ($this->observers AS $k => $v)
			{
				if (preg_match($v['pattern'], $path))
				{
					$result[$k] = $k;
				}
			}
			$this->path2observersCache[$path] = $result;
		}

		return $this->path2observersCache[$path];
	}
}

class XMLParserNode implements ArrayAccess
{
	private $tag;
	private $content;
	private $attributes;
	private $children = array();
	private $parentNode;
	private $level = 1;

	public function __construct($tag)
	{
		$this->tag = $tag;
	}

	public function setAttributes($attributes)
	{
		$this->attributes = $attributes;
	}

	public function setContent($content)
	{
		$this->content = $content;
	}

	public function setLevel($level)
	{
		$this->level = $level;
	}

	public function getContent()
	{
		return $this->content;
	}

	public function getTag()
	{
		return $this->tag;
	}

	public function getLevel()
	{
		return $this->level;
	}

	public function addChild(&$element)
	{
		$element->setParent($this);
		$this->children[$element->getTag()][] = $element;
	}

	public function setParent(&$node)
	{
		$this->parentNode = $node;
	}

	public function &getParent()
	{
		return $this->parentNode;
	}

	// array access
	public function offsetExists($key)
	{
		return isset($this->attributes[strtoupper($key)]);
	}

	public function offsetGet($key)
	{
		return $this->attributes[strtoupper($key)];
	}

	public function offsetSet($key, $value)
	{
		$this->attributes[strtoupper($key)] = $value;
	}

	public function offsetUnset($key)
	{
		unset($this->attributes[strtoupper($key)]);
	}

	public function get($name)
	{
		if (array_key_exists($name, $this->attributes))
		{
			return $this->attributes[$name];
		}
		else if (array_key_exists($name, $this->children))
		{
			if ($this->children[$name][0])
			{
				return $this->children[$name][0]->getContent();
			}
			else
			{
				return null;
			}
		}
		else
		{
			return null;
		}
	}

	public function getArray()
	{
		
	}

	// magic
	public function __get($name)
	{
		if (array_key_exists($name, $this->children))
		{
			return $this->children[$name];
		}
		return null;
	}
}

?>