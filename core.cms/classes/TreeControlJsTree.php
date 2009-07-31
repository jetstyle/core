<?php
Finder::useClass('TreeControl');

class TreeControlJsTree extends TreeControl
{
	//templates
	protected $template = "tree_control_js_tree.html";
	protected $template_trash_show = "list_simple.html:trash_show";
	protected $template_trash_hide = "list_simple.html:trash_hide";

	public function handle()
	{
		$action = $_REQUEST['action'];

		switch($action)
		{
			case 'update':
				echo $this->updateTreeStruct();
				die();
			break;

			case 'json':
				header("Content-type: text/x-json; charset=".$this->xmlEncoding);

				$nodeId = $_GET['id'];
				$currentId = intval($_GET['cid']);

				if ($nodeId == '0')
				{
					$this->id = $currentId ? $currentId : $this->getRootId();
					$parents = $this->getParentsForItem($this->id);
					$parents[] = $this->id;
					$this->loadByParents($parents);
					echo $this->toJSON();
				}
				else
				{
					$this->loadByParents(array($nodeId));

					$nodeId = str_replace('node-', '', $nodeId);

					echo $this->toJSON($nodeId);
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

				$treeParams = array(
					'current_id' => ($_GET['full_id'] ? $_GET['full_id'] : 'node-'.$this->id),
					'source_url' => RequestInfo::hrefChange(RequestInfo::$baseUrl."do/".$this->config['module_path'], array('action' => 'json', $this->idGetVar => '', 'cid' => $this->id)),
					'update_url' => RequestInfo::hrefChange(RequestInfo::$baseUrl."do/".$this->config['module_path'], array('action' => 'update')),
					'ajax_auto_loading' => $this->config['ajaxAutoLoading'],
				);


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
				$treeParams['go_url'] = $url;

                Finder::useClass('Json');
                $treeParams['hide_buttons'] = Json::encode($this->config['hide_buttons']);

				if ($_COOKIE['tree_control_btns'] == 'true')
				{
				 	$treeParams['show_controls'] = true;
				}

				$checkTree = false;

				if (!$this->config['ajaxAutoLoading'])
				{
					$this->load();
					if (empty($this->children[$this->items[$this->getRootId()]['_parent']]))
					{
						$checkTree = true;
					}
				}
				else
				{
					$checkTree = true;
				}

				if ($checkTree)
				{
					$result = $this->getNodesCountForRoot();
					if (!$result['total'])
					{
						$this->createRootNode();

						if (!$this->config['ajaxAutoLoading'])
						{
							$this->loaded = false;
							$this->items = array();
							$this->children = array();
							$this->load();
						}
					}
					// all nodes are deleted
					elseif ($result['total'] == $result['deleted'])
					{
						$treeParams['all_deleted'] = true;
					}
				}

				$treeParams['data'] = $this->toJSON();
				$treeParams['level_limit'] = $this->level_limit;

				$treeParams['disable_drag'] = $this->config['disable_drag'] ? true : false;

				$this->tpl->set('tree_params', $treeParams);
			break;
		}
	}

	protected function loadByParents($parents)
	{
		foreach($parents as &$parent)
		{
        	$parent = str_replace('node-', '', $parent);
		}
		$where = empty($parents) ? '' : "_parent IN (".implode(',',$parents).")";
    	$this->load($where);
	}

	public function getHtml()
	{
        $this->renderFilters();
		$this->renderTrash();
		return $this->tpl->Parse( $this->template);
	}

	public function toJSON($fromNode = null)
	{
		$this->toRoot = array();
		$c = $this->items[ $this->id ];
		do
		{
			$this->toRoot[$c['id']] = $c['id'];
			$c = $this->items[$c['_parent']] ;
		} while($c);

		//print_r($this->children);

		if ($fromNode)
		{
			$data = $this->treeParse($this->children[$fromNode]);
		}
		else
		{
			$data = $this->treeParse($this->children[$this->items[$this->getRootId()]['_parent']]);
		}

		if (function_exists('json_encode'))
		{
			return json_encode($data);
		}
		else
		{
			Finder::useClass('Json');
			return Json::encode($data);
		}
	}

	protected function createRootNode()
	{
		$_REQUEST['newtitle'] = iconv('cp1251', 'UTF-8', 'Узел дерева');
		$id = parent::addNode();

		if ($this->config['denyDropToRoot'])
		{
			Locator::get('db')->execute('
				UPDATE ??'.$this->config['table'].' SET _supertag = "", _path = "" WHERE id = '.$id.'
			');
		}

	}

	protected function getNodesCountForRoot()
	{
		$data = array('total' => 0, 'deleted' => 0);

		$result = $this->db->execute("
			SELECT ".$this->idField.", _state
			FROM ??".$this->config['table']."
			WHERE _parent = 0 AND _state>=0 " . ($this->config['where'] ? " AND ".$this->config['where'] : "") . "
		");

		while ($r = $this->db->getRow($result))
		{
			$data['total']++;
			if ($r['_state'] == 2)
			{
				$data['deleted']++;
			}
		}

		return $data;
	}

	protected function renderTrash()
	{
		//render trash switcher
		if (!$this->config['hide_controls']['show_trash'])
		{
			$show_trash = $_GET['_show_trash'];
			$this->tpl->set( '_show_trash_href', RequestInfo::hrefChange('', array('_show_trash' => !$show_trash)));
			$this->tpl->parse( $show_trash ? $this->template_trash_hide : $this->template_trash_show, '__trash_switch' );
		}
	}

	protected function treeParse($data)
	{
		$result = array();

		if(is_array($data))
		{
			foreach($data AS $id)
			{
				$state = '';

				if (is_array($this->children[$id]) && count($this->children[$id]))
				{
					$state = 'closed';
					if (in_array($id, $this->toRoot))
					{
						$state = 'open';
					}
				}
				elseif ($this->config['ajaxAutoLoading'] && $this->items[$id]['has_children'])
				{
					$state = 'closed';
				}

				$result[] = array(
					'data' => iconv('cp1251', 'utf-8', $this->_getTitle($this->items[$id])),
					'attributes' => array(
						'id' => 'node-'.$id,
						'data' => '{type: "'.( ($this->items[$id]['_level'] == 1 && $this->config['denyDropToRoot']) ? 'root' : 'node').'",path: "'.$this->items[$id]['_path'].'",form_config: "'.$this->items[$id]['form_config'].'"}',
						'class' => ($this->items[$id]['_state'] == 1 ? 'hidden' : ($this->items[$id]['_state'] == 2 ? 'deleted' : '')),
					),
					'level' => $this->items[$id]['_level'],
					'state' => $state,
					'children' => $this->treeParse($this->children[$id]),
					'custom_buttons' => $this->items[$id]['custom_buttons'],
					'hide_buttons' => $this->items[$id]['hide_buttons'],
				);
				if (
					$this->config['customIconsField'] &&
					$this->config['customIcons'][$this->items[$id]['controller']]
				)
					$result[count($result)-1]['icon'] = $this->config['customIcons'][$this->items[$id]['controller']];
			}
		}

		return $result;
	}

	protected function _getTitle(&$node)
	{
		$_title = $node['title_short'] ? $node['title_short'] : $node['title'];
		$_title = $_title ? $_title : 'node_'.$node[$this->idField];

		return $_title;
	}

	protected function addNode()
	{
		$id = parent::addNode();
		Locator::get('db')->execute('
			UPDATE ??'.$this->config['table'].' SET _supertag = "" WHERE id = '.$id.'
		');
		return $id;
	}

	protected function saveTitle($id, $title)
	{
		$db = &$this->db;

		$node = $this->getItem($id);

		if ($node['id'])
		{
			$sql = "UPDATE ??".$this->config['table']." SET title=".$db->quote($title).", title_pre = ".$db->quote($this->tpl->action('typografica', $title));

			$supertag = '';
			if (!$node['_supertag'])
			{
				Finder::useClass('Translit');
				$translit =& new Translit();
				$supertag = $translit->supertag($title, 20);
				$sql .= ", _supertag = ".$db->quote($supertag);
			}

			$sql .= " WHERE id=".$db->quote($id);
			$db->execute($sql);

			if ($supertag)
			{
				$this->updateTreePathes($this->config['table'], $node['id'], $this->config['allow_empty_supertag'], $this->config['where']);
			}
		}
	}

	public function getItems($parent)
	{
     	$this->loadByParents(array($parent));
     	return array('items'=>$this->items, 'children'=>$this->children);
	}
}
?>
