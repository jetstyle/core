<?php
class TreeControl
{
	protected $rh;
	protected $config;

	//templates
	protected $template = "tree_control.html";
	protected $template_trash_show = "list_simple.html:TrashShow";
	protected $template_trash_hide = "list_simple.html:TrashHide";
	
	protected $xmlEncoding = "windows-1251";

	protected $loaded = false;

	protected $idField = "id";
	protected $idGetVar = "id";

	protected $items = array();
	protected $childs = array();

	protected $rootId = 0;

	protected $redirectIfEmptyId = false;
	
	protected $level_limit = 3;

	public function __construct( &$config )
	{
		$this->config =& $config;
		$this->config->SELECT_FIELDS = array_merge( $this->config->SELECT_FIELDS, array('_parent','_state','_left','_right','_level') );

		if ($this->config->xmlEncoding)
		{
			$this->xmlEncoding = $this->config->xmlEncoding;
		}
		
		if ($this->config->redirectIfEmptyId)
		{
			$this->redirectIfEmptyId = $this->config->redirectIfEmptyId;
		}
		
		if ($this->config->level_limit)
		{
			$this->level_limit = $this->config->level_limit;
		}

		$this->config->hide_buttons['addChild'][$this->level_limit] = true;
		
		$this->rh = &$config->rh;

		$this->id = $this->rh->ri->get($this->idGetVar);
	}

	public function handle()
	{
		$action = $_REQUEST['action'];

		switch($action)
		{
			case 'update':
				$title = $_REQUEST['title'];
				$id    = $_REQUEST['itemId'];
				if (!empty($title))
				{
					$res = $this->saveTitle($id, $title);
				}
				else
				{
					$res = $this->updateTreeStruct();
				}
				echo $res;
				die();
			break;
					
			case 'xml':
				header("Content-type: text/xml; charset=".$this->xmlEncoding);
				$this->load();
				echo $this->toXML();
				die();
			break;

			default:
				
				if (!$this->id && $this->redirectIfEmptyId)
				{
					$rootId = $this->getRootId();
					if ($rootId && !defined('UNIT_TEST'))
					{
						$this->rh->redirect($this->rh->ri->hrefPlus('', array($this->idGetVar => $rootId)));
					}
				}
				$show_trash = $_GET['_show_trash'];
				
				$this->rh->tpl->set('_url_xml', $this->rh->ri->hrefPlus("do/".$this->config->moduleName."/tree", array('action' => 'xml')));
				$this->rh->tpl->set('_url_connect', $this->rh->ri->hrefPlus("do/".$this->config->moduleName."/tree", array('action' => 'update')));
				$url = $this->rh->ri->hrefPlus('', array('id' => ''));
				$pos = strpos($url, '?');
				if ($pos !== false)
				{
					if (($pos + 1) != strlen($url))
					{
						$url .= '&';
					}
				}
				else
				{
					$url .= '?';
				}
				$this->rh->tpl->set( '_href', $url);

				if ($_COOKIE['tree_control_btns'] == 'true')
				{
					$this->rh->tpl->set("toggleEditTreeClass", "class='toggleEditTreeClass-Sel'");
				}
				
				if (!$this->config->ajaxLoad)
				{
					$this->load();
					$this->rh->tpl->set('_xml_string', str_replace(array('"', "\n"), array('\"', ""), $this->toXML()));
				}				
				
			break;
		}
	}

	public function getHtml()
	{
		$this->renderTrash();
		return $this->rh->tpl->Parse( $this->template);
	}

	public function toXML()
	{
		//start XML
		$str = "<?xml version=\"1.0\" encoding=\"".$this->xmlEncoding."\" ?>\n";
		$str .= "<tree id=\"0\">";

		$this->toRoot = array();
		$c = $this->items[ intval($this->id) ];
		do
		{
			$this->toRoot[$c['id']] = $c['id'];
			$c = $this->items[$c['_parent']] ;
		} while($c);

		$str .= $this->treeParse($this->children[$this->items[$this->getRootId()]['_parent']]);
		$str .= "</tree>";
		return $str;
	}

	protected function renderTrash()
	{
		//render trash switcher
		if (!$this->config->HIDE_CONTROLS['show_trash'])
		{
			$show_trash = $_GET['_show_trash'];
			$this->rh->tpl->set( '_show_trash_href', $this->rh->ri->hrefPlus('', array('_show_trash' => !$show_trash)));
			$this->rh->tpl->parse( $show_trash ? $this->template_trash_hide : $this->template_trash_show, '__trash_switch' );
		}
	}
		
	protected function treeParse($data)
	{
		if(is_array($data))
		{
			foreach($data AS $id)
			{
				$title = $this->xmlQuote($this->_getTitle($this->items[$id]));
				$buttons = $this->_getButtons($this->items[$id]);
				
				if(is_array($this->children[$id]))
				{
					$childs = $this->treeParse($this->children[$id]);
					$out.= '<item text="'.$title.'" id="'.$id.'" '.($this->toRoot[$id] ? 'open="1"' : '').' child="1" '.($this->id == $id ? 'select="true"' : '').' '.$buttons.'>'.$childs."</item>\n";
				}
				else
				{
					$out.= '<item text="'.$title.'" id="'.$id.'" child="0" '.($this->id == $id ? 'select="true"' : '').' '.$buttons." />\n";
				}
			}
		}

		return $out;
	}

	protected function load()
	{
		if (!$this->loaded)
		{
			$this->loaded = true;
				
			$result = $this->rh->db->execute("
				SELECT ".implode(", ", $this->config->SELECT_FIELDS)."
				FROM ??".$this->config->table_name."
				WHERE ".( $_GET['_show_trash'] ? '_state>=0' : "_state <>2 " ) . ($this->config->where ? ' AND ' . $this->config->where : '')." ".($this->level_limit ? " AND _level <= ".$this->level_limit : "")."
				ORDER BY _order ASC
			");
				
			if ($result)
			{
				while ($r = $this->rh->db->getRow($result))
				{
					$this->items[$r[$this->idField]] = $r;
					$this->children[$r['_parent']][] = $r[$this->idField];
				}
			}
		}
	}

	protected function updateTreeStruct()
	{
		$rh =& $this->rh;
		$db =& $rh->db;

		if( $_REQUEST['add'])
		{
			$id = $this->addNode();
			
			$this->load();
			$this->restore();
			
			return $id;
		}
		elseif($delete = intval($_REQUEST['delete']))
		{
			$node = $this->deleteNode($delete);

			if($node['id'])
			{
				// если удалили совсем, тогда пересчитаем дерево
				if ($node['_state'] == 2)
				{
					$this->load();
					$this->restore();
				}
				
				return '1';
			}
			else
			{
				return '0';
			}
		}
		elseif($_REQUEST['change'])
		{
			$itemId = intval($_REQUEST['id']);
			$this->id = $itemId;
			$targetId = intval($_REQUEST['target']);
			$beforeId = intval($_REQUEST['before']);

			if($beforeId)
			{
				$node = $db->queryOne("
					SELECT _parent, _order
					FROM ??". $this->config->table_name ."
					WHERE ".$this->idField." = '".$beforeId."'
				");

				$db->query("
					UPDATE ??". $this->config->table_name ."
					SET _order = _order + 1
					WHERE _order >= " . $node['_order'] . " AND _parent = '" . $node['_parent'] . "'
				");
			}
			else
			{
				$node = $db->queryOne("
					SELECT (MAX(_order) + 1) AS _order
					FROM ??". $this->config->table_name ."
					WHERE _parent = '".$targetId."'
				");
			}

			$db->query("
				UPDATE ??". $this->config->table_name ."
				SET _order = " . intval($node['_order']) . ", _parent = '".$targetId."'
				WHERE ".$this->idField." = " . $itemId  . "
			");

			$this->load();
			$this->restore();
				
			include( $rh->findScript_('handlers','_update_tree_pathes') );
				
			return '1';
		}
		return '0';
	}

	protected function addNode()
	{
		$rh =& $this->rh;
		$db =& $rh->db;

		$rh->useClass('Translit');
		$translit =& new Translit();

		$node = array();

		$node['title'] = iconv("UTF-8", "CP1251", $_REQUEST['newtitle']);
		if(strlen($node['title']) == 0)
		{
			$node['title'] = 'Новый узел';
		}

		$node['title_pre'] = $this->rh->tpl->action('typografica', $node['title']);
		$node['parent'] = intval($_REQUEST['parent']);
		$node['supertag'] = $translit->translateLink($node['title'], 20);

		$parentNode = $db->queryOne("
			SELECT _path
			FROM ??". $this->config->table_name ."
			WHERE id = '".$node['parent']."'
		");

		$node['_path'] = $parentNode['_path'] ? $parentNode['_path'].'/'.$node['supertag'] : $node['supertag'];

		$order = $db->queryOne("
			SELECT (MAX(_order) + 1) AS _max
			FROM ??". $this->config->table_name ."
			WHERE _parent = '".$node['parent']."'
		");		

		$id = $db->insert("
			INSERT INTO ". $this->rh->db_prefix.$this->config->table_name ."
			(title, title_pre, _parent, _supertag, _path, _order, _state)
			VALUES
			(".$this->rh->db->quote($node['title']).", ".$this->rh->db->quote($node['title_pre']).", ".$this->rh->db->quote($node['parent']).", ".$this->rh->db->quote($node['supertag']).", ".$this->rh->db->quote($node['_path']).", ".$this->rh->db->quote($order['_max']).", 1)
		");

		return $id;
	}
	
	function deleteNode($nodeId)
	{
		$rh =& $this->rh;
		$db =& $rh->db;

		$node = $db->queryOne("
			SELECT id, _left, _right, _state
			FROM ??". $this->config->table_name ."
			WHERE id = '".$nodeId."'
			");

		if (is_array($node) && !empty($node))
		{
			// удаляем совсем
			if ($node['_state'] == 2)
			{
				$db->query("
					DELETE FROM ". $this->rh->db_prefix.$this->config->table_name ."
					WHERE _left >= ".$node['_left']." AND _right <= ".$node['_right']."
				");
			}
			// метим
			else
			{
				$db->query("
					UPDATE ". $this->rh->db_prefix.$this->config->table_name ."
					SET _state = 2
					WHERE _left >= ".$node['_left']." AND _right <= ".$node['_right']."
				");
			}
		}

		return $node;
	}
	
	protected function getRootId()
	{
		if (!$this->rootId)
		{
			$result = $this->rh->db->queryOne("
				SELECT ".$this->idField."
				FROM ??".$this->config->table_name."
				WHERE _parent = 0 
				ORDER BY _order ASC
			");
				
			if ($result[$this->idField])
			{
				$this->rootId = $result[$this->idField];
			}
		}
		return $this->rootId;
	}

	protected function xmlQuote($str)
	{
		return htmlspecialchars($str, ENT_COMPAT, $this->xmlEncoding);
	}

	protected function _getTitle(&$node)
	{
		$_title = $node['title_short'] ? $node['title_short'] : $node['title'];
		$_title = $_title ? $_title : 'node_'.$node[$this->idField];

		if ($node['_state'] == 1)
		{
			$_title = $_title  .' [скрыт]';
		}
		elseif ($node['_state'] > 1)
		{
			$_title = $_title  .' [удален]';
		}
		 
		$_title = preg_replace( "/<.*?>/is", '', $_title);
		return $_title;
	}

	protected function _getButtons(&$node)
	{
		$buttons = array('addChild', 'addBrother', 'del');
		$result = '';
		
		if (is_array($this->config->hide_buttons) && !empty($this->config->hide_buttons))
		{
			foreach ($buttons AS $buttonName)
			{
				if (isset($this->config->hide_buttons[$buttonName]))
				{
					if (is_array($this->config->hide_buttons[$buttonName]))
					{
						if (isset($this->config->hide_buttons[$buttonName][$node['_level']]))
						{
							$result .= ' hide'.ucfirst($buttonName).'Button="true" ';
						}
					}
					else
					{
						$result .= ' hide'.ucfirst($buttonName).'Button="true" ';
					}
				}
			}
		}
		
		return $result;
	}
	
	protected function saveTitle($id, $title)
	{
		$title = iconv("UTF-8", "CP1251", $title);

		$sql = "UPDATE ??".$this->config->table_name." SET title_short=".$this->rh->db->quote($title)." WHERE id=".$this->rh->db->quote($id);   
		$this->rh->db->execute($sql);
		return $sql;
	}

	protected function restore( $parent_id=0, $left=0, $order = 0 )
	{
		//shortcuts
		$node =& $this->items[ $parent_id ];

		//_level
		if($node[$this->idField])
		{
			$node['_level'] = $this->items[ $node['_parent'] ]['_level'] + 1;
		}

		/* Taken from http://www.sitepoint.com/article/1105/3 */

		// the right value of this node is the left value + 1
		$right = $left + 1;

		// get all children of this node
		$A =& $this->children[$parent_id];

		$n = count($A);
		for($i=0;$i<$n;$i++)
		{
			// recursive execution of this function for each
			// child of this node
			// $right is the current right value, which is
			// incremented by the rebuild_tree function
			$right = $this->restore( $A[$i], $right, $i);
		}

		// we've got the left value, and now that we've processed
		// the children of this node we also know the right value
		$node['_left'] = $left;
		$node['_right'] = $right;

		//echo $node['_level'].' '.$order.' '.$node['title'].'<br />';

		//store in DB
		//    print("UPDATE ".$this->table_name." SET _level='".$node['_level']."', _left='".$node['_left']."', _right='".$node['_right']."' WHERE id='".$node['id']."'<br>\n");
		$this->rh->db->execute("UPDATE ??".$this->config->table_name." SET _level='".$node['_level']."', _left='".$node['_left']."', _right='".$node['_right']."', _order = '".$order."' WHERE ".$this->idField."='".$node[$this->idField]."'");

		// return the right value of this node + 1
		return $right + 1;
	}
}
?>
