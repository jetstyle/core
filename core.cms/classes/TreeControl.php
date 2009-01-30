<?php
class TreeControl
{
	protected $tpl = null;
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
		$this->config->SELECT_FIELDS = array_merge( $this->config->SELECT_FIELDS, array('_parent','_state','_left','_right','_level','_path','_supertag') );

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

		if (!array_key_exists('addChild', $this->config->hide_buttons))
		{
			$this->config->hide_buttons['addChild'][$this->level_limit] = true;
		}

		$this->tpl = &Locator::get('tpl');
		$this->db = &Locator::get('db');

		$this->id = intval(RequestInfo::get($this->idGetVar));
	}


	public static function updateTreePathes($tableName, $id, $allow_empty_supertag = false, $where = '', $noRoot=null)
	{
    	$db = &Locator::get('db');
		$root = $db->queryOne("SELECT id,_left,_right,_path,_parent FROM ??".$tableName." WHERE id='".$id."' ".($where ? " AND " . $where : "")." ");
		if ($root) {
			//грузим поддерево
			$result = $db->execute("
				SELECT id, _supertag, _parent, _path
				FROM ??".$tableName."
				WHERE _left>= ".$root['_left']." AND _right <= ".$root['_right']." ".($where ? " AND " . $where : "")."
			");

			if ($result) {
				$tree = array('children' => array(), 'items' => array());
				while ($r = $db->getRow($result)) {
					$tree['children'][$r['_parent']][] = $r['id'];
					$tree['items'][$r['id']] = $r;
				}

				$parent = $db->queryOne("
					SELECT id, _supertag, _parent, _path
					FROM ??".$tableName."
					WHERE id = ".$root['_parent']." ".($where ? " AND " . $where : "")."
				");
				if ($parent) $tree['items'][$parent['id']] = $parent;

				//обходим поддерево
				$STACK[] = $root['id'];
				while(count($STACK)) {
					$id = array_pop($STACK);
					//собираем детей
					if (is_array($tree['children'][$id])) {
						foreach( $tree['children'][$id] as $_id ) {
							$STACK[] = $_id;
						}
					}

					//модифицируем узел
					$r = $tree['items'][$id];
					if ($r['_parent'] == 0 && $parent_id !== 0 && !$noRoot )
					{
						$r['_path'] = '';
						$r['_supertag'] = '';
					}
				    else
				    {
						$parentTag = $tree['items'][ $r['_parent'] ]['_path'];
						$r['_path'] = ($parentTag ? $parentTag.'/' : '').$r["_supertag"];
					}

					$db->execute("UPDATE ".DBAL::$prefix.$tableName." SET _supertag='".$r["_supertag"]."',_path='".$r['_path']."' WHERE id='".$r['id']."'");
					$tree['items'][$id] = $r;
				}
			}
		}
	}



	public function handle()
	{
		$action = $_REQUEST['action'];

		switch($action)
		{
			case 'update':
				$res = $this->updateTreeStruct();
				echo $res;
				die();
			break;

			case 'xml':
				header("Content-type: text/xml; charset=".$this->xmlEncoding);
				if ($this->config->ajaxAutoLoading)
				{
					if ($_GET['autoload'])
					{
						$this->load("_parent = ".$this->id);
						echo $this->toXML($this->id);
					}
					else
					{
						$parents = $this->getParentsForItem($this->id ? $this->id : $this->getRootId());
						$this->load("_parent IN('".implode("','", $parents)."')");
						echo $this->toXML();
					}
				}
				else
				{
					$this->load();
					echo $this->toXML();
				}
				die();
			break;

			default:

				if (!$this->id && $this->redirectIfEmptyId)
				{
					$rootId = $this->getRootId();
					if ($rootId && !defined('UNIT_TEST'))
					{
						Controller::redirect(RequestInfo::hrefChange('', array($this->idGetVar => $rootId)));
					}
				}
				$show_trash = $_GET['_show_trash'];

				$this->tpl->set('_url_xml', RequestInfo::hrefChange(RequestInfo::$baseUrl."do/".$this->config->componentPath, array('action' => 'xml')));
				$this->tpl->set('_url_connect', RequestInfo::hrefChange(RequestInfo::$baseUrl."do/".$this->config->componentPath, array('action' => 'update')));
				$url = RequestInfo::hrefChange('', array('id' => ''));
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
				$this->tpl->set( '_href', $url);

				if ($_COOKIE['tree_control_btns'] == 'true')
				{
					$this->tpl->set("toggleEditTreeClass", "class='toggleEditTreeClass-Sel'");
				}

				if (!$this->config->ajaxLoad)
				{
					if ($this->config->ajaxAutoLoading)
					{
						$parents = $this->getParentsForItem($this->id ? $this->id : $this->getRootId());
						$this->load("_parent IN('".implode("','", $parents)."')");
					}
					else
					{
						$this->load();
					}
					$this->tpl->set('_xml_string', str_replace(array('"', "\n"), array('\"', ""), $this->toXML()));
				}

				$this->tpl->set('_tree_allow_drop_to_root', $this->config->allowDropToRoot);
				$this->tpl->set('_tree_autoloading', $this->config->ajaxAutoLoading);
				$this->tpl->set('_tree_autoloading_url', RequestInfo::hrefChange(RequestInfo::$baseUrl."do/".$this->config->componentPath, array('action' => 'xml', $this->idGetVar => '', 'autoload' => '1')));

			break;
		}
	}

	public function getHtml()
	{
		$this->renderTrash();
		return $this->tpl->Parse( $this->template);
	}

	public function toXML($treeId = 0)
	{
		//start XML
		$str = "<?xml version=\"1.0\" encoding=\"".$this->xmlEncoding."\" ?>\n";
		$str .= "<tree id=\"".$treeId."\">";

		$this->toRoot = array();
		$c = $this->items[ intval($this->id) ];
		do
		{
			$this->toRoot[$c['id']] = $c['id'];
			$c = $this->items[$c['_parent']] ;
		} while($c);

		if ($this->config->ajaxAutoLoading)
		{
			if (0 == $treeId)
			{
				$str .= $this->treeParse($this->children[$this->items[$this->getRootId()]['_parent']]);
			}
			else
			{
				$str .= $this->treeParse($this->children[$this->id]);
			}
		}
		else
		{
			$str .= $this->treeParse($this->children[$this->items[$this->getRootId()]['_parent']]);
		}

		$str .= "</tree>";
		return $str;
	}

	protected function renderTrash()
	{
		//render trash switcher
		if (!$this->config->HIDE_CONTROLS['show_trash'])
		{
			$show_trash = $_GET['_show_trash'];
			$this->tpl->set( '_show_trash_href', RequestInfo::hrefChange('', array('_show_trash' => !$show_trash)));
			$this->tpl->parse( $show_trash ? $this->template_trash_hide : $this->template_trash_show, '__trash_switch' );
		}
	}

	protected function treeParse($data)
	{
		$out = '';

		if(is_array($data))
		{
			foreach($data AS $id)
			{
				$title = $this->xmlQuote($this->_getTitle($this->items[$id]));
				$buttons = $this->_getButtons($this->items[$id]);

				if(is_array($this->children[$id]) || $this->config->ajaxAutoLoading)
				{
					if ($this->config->ajaxAutoLoading)
					{
						if (($this->items[$id]['_right'] - $this->items[$id]['_left']) == 1)
						{
							$_child = 0;
						}
						else
						{
							$_child = 1;
						}
					}
					else
					{
						$_child = 1;
					}
					$childs = $this->treeParse($this->children[$id]);
					$out.= '<item text="'.$title.'" id="'.$id.'" '.($this->toRoot[$id] ? 'open="1"' : '').' child="'.$_child.'" '.($this->id == $id ? 'select="true"' : '').' '.$buttons.'>'.$childs."</item>\n";
				}
				else
				{
					$out.= '<item text="'.$title.'" id="'.$id.'" child="0" '.($this->id == $id ? 'select="true"' : '').' '.$buttons." />\n";
				}
			}
		}

		return $out;
	}

	public function load($where = null)
	{
		if (!$this->loaded)
		{
			$this->loaded = true;
			$db = &$this->db;
			$result = $db->execute("
				SELECT ".implode(", ", $this->config->SELECT_FIELDS)."
				FROM ??".$this->config->table_name."
				WHERE ".( $_GET['_show_trash'] ? '_state>=0' : "_state <>2 " ) . ($where ? ' AND ' . $where : '') . ($this->config->where ? ' AND ' . $this->config->where : '')." ".($this->level_limit ? " AND _level <= ".$this->level_limit : "")."
				ORDER BY _order ASC
			");

			if ($result)
			{
				while ($r = $db->getRow($result))
				{
					$this->items[$r[$this->idField]] = $r;
					$this->children[$r['_parent']][] = $r[$this->idField];
				}
			}
		}
	}

	protected function getItem($id)
	{
		return $this->loadItem($id);
	}

	protected function loadItem($id)
	{
		return $this->db->queryOne("
			SELECT ".implode(", ", $this->config->SELECT_FIELDS)."
			FROM ??".$this->config->table_name."
			WHERE id = ".intval($id)." ". ($this->config->where ? ' AND ' . $this->config->where : '')." ".($this->level_limit ? " AND _level <= ".$this->level_limit : "")."
		");
	}

	protected function getParentsForItem($id)
	{
		$parents = array();

		$item = $this->getItem($id);
		if (!$item[$this->idField])
		{
			return $parents;
		}
		elseif ($item['_level'] == 1)
		{
			$parents[] = 0;
		}
		elseif ($item['_level'] == 2)
		{
			$parents[] = 0;
			$parents[] = $item['_parent'];
		}
		else
		{
			$db = &$this->db;
			$result = $db->execute("
				SELECT _parent
				FROM ??".$this->config->table_name."
				WHERE _left <= ".$item['_left']." AND _right >= ".$item['_right']." ". ($this->config->where ? ' AND ' . $this->config->where : '')."
			");

			if ($result)
			{
				while ($r = $db->getRow($result))
				{
					$parents[] = $r['_parent'];
				}
			}
		}

		//$parents[] = $item['id'];

		return $parents;
	}

	protected function updateTreeStruct()
	{
		if ($_REQUEST['rename'])
		{
			$id = intval($_REQUEST['id']);
			$title = trim($_REQUEST['title']);

			if ($id && strlen($title) > 0)
			{
				$title = iconv('utf-8', 'cp1251', $title);
				$this->saveTitle($id, $title);
			}
			return '0';
		}
		elseif( $_REQUEST['add'])
		{
			$id = $this->addNode();

			$this->load();
			$this->restore();

//			$this->killOutsiders();

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

//					$this->killOutsiders();
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

			$db = &$this->db;

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

//			$this->killOutsiders();

			$this->updateTreePathes($this->config->table_name, $this->id, $this->config->allow_empty_supertag, $this->config->where);

			return '1';
		}
		return '0';
	}



	protected function addNode()
	{
		$db =& $this->db;

		Finder::useClass('Translit');
		$translit =& new Translit();

		$node = array();

		$node['title'] = iconv("UTF-8", "CP1251", $_REQUEST['newtitle']);
		if(strlen($node['title']) == 0)
		{
			$node['title'] = 'Новый узел';
		}

		$node['title_pre'] = $this->tpl->action('typografica', $node['title']);
		$node['parent'] = intval($_REQUEST['parent']);
		$node['before'] = intval($_REQUEST['before']);
		$node['supertag'] = $translit->supertag($node['title'], 20);

		$parentNode = $db->queryOne("
			SELECT _path
			FROM ??". $this->config->table_name ."
			WHERE id = '".$node['parent']."'
		");

		$node['_path'] = $parentNode['_path'] ? $parentNode['_path'].'/'.$node['supertag'] : $node['supertag'];

		$order = null;

		if($node['before'])
		{
			$beforeNode = $db->queryOne("
				SELECT _parent, _order
				FROM ??". $this->config->table_name ."
				WHERE ".$this->idField." = '".$node['before']."'
			");

			if (is_array($beforeNode) && is_numeric($beforeNode['_order']))
			{
				$db->query("
					UPDATE ??". $this->config->table_name ."
					SET _order = _order + 1
					WHERE _order >= " . $db->quote($beforeNode['_order']) . " AND _parent = '" . $beforeNode['_parent'] . "'
				");

				$order = $beforeNode['_order'];
			}
		}

		if (!is_numeric($order))
		{
			$order = $db->queryOne("
				SELECT (MAX(_order) + 1) AS _max
				FROM ??". $this->config->table_name ."
				WHERE _parent = '".$node['parent']."'
			");

			$order = intval($order['_max']);
		}

		if (isset($this->config->INSERT_FIELDS) && is_array($this->config->INSERT_FIELDS))
		{
			foreach ($this->config->INSERT_FIELDS AS $fieldName => $fieldValue)
			{
				$additionFields .= ','.$fieldName;
				$additionValues .= ','.$db->quote($fieldValue);
			}
		}

		$id = $db->insert("
			INSERT INTO ". DBAL::$prefix.$this->config->table_name ."
			(title, title_pre, _parent, _supertag, _path, _order, _state " . $additionFields . ")
			VALUES
 			(".$this->db->quote($node['title']).", ".$db->quote($node['title_pre']).", ".$db->quote($node['parent']).", ".$db->quote($node['supertag']).", ".$db->quote($node['_path']).", ".$db->quote($order).", 1 ".$additionValues.")
		");

		return $id;
	}

	function deleteNode($nodeId)
	{
		$db = &$this->db;

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
					DELETE FROM ??". $this->config->table_name ."
					WHERE _left >= ".$node['_left']." AND _right <= ".$node['_right']." ".($this->config->where ? " AND ".$this->config->where : "")."
				");
			}
			// метим
			else
			{
				$db->query("
					UPDATE ??". $this->config->table_name ."
					SET _state = 2
					WHERE _left >= ".$node['_left']." AND _right <= ".$node['_right']." ".($this->config->where ? " AND ".$this->config->where : "")."
				");
			}
		}

		return $node;
	}

	protected function getRootId()
	{
		if (!$this->rootId)
		{
			$result = $this->db->queryOne("
				SELECT ".$this->idField."
				FROM ??".$this->config->table_name."
				WHERE _parent = 0 AND _state IN (0,1,2) " . ($this->config->where ? " AND ".$this->config->where : "") . "
				ORDER BY _state ASC, _order ASC
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
		$str = html_entity_decode($str, ENT_QUOTES, $this->xmlEncoding);
		$str = htmlspecialchars($str, ENT_QUOTES, $this->xmlEncoding);
		return $str;
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
		$db = &$this->db;

		$sql = "UPDATE ??".$this->config->table_name." SET title=".$db->quote($title).", title_pre = ".$db->quote($this->tpl->action('typografica', $title))." WHERE id=".$db->quote($id);
		$db->execute($sql);
		return $sql;
	}

	protected function killOutsiders()
	{
		$rootId = $this->getRootId();

		if (!$rootId)
		{
			return;
		}

		$S = array($rootId);
		$IDS = array();

		while(count($S))
		{
			$id = array_pop($S);
			if(is_array($this->children[$id]))
			{
				$S = array_merge($S, $this->children[$id]);
			}
			$IDS[] = $id;
		}

		if (!empty($IDS))
		{
			$this->db->execute("
				UPDATE ??".$this->config->table_name."
				SET _parent = 0, _left = 0, _right = 0, _state = 2
				WHERE _state < 2 AND id NOT IN ('".implode("','", $IDS)."')"
			);
		}
	}

	public function restore( $parent_id=0, $left=0, $order = 0 )
	{
		//shortcuts
		$node =& $this->items[ $parent_id ];
		$db = &$this->db;

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
		$db->execute("UPDATE ??".$this->config->table_name." SET _level='".$node['_level']."', _left='".$node['_left']."', _right='".$node['_right']."', _order = '".$order."' WHERE ".$this->idField."='".$node[$this->idField]."'");

		// return the right value of this node + 1
		return $right + 1;
	}
}
?>
