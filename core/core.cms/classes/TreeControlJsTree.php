<?php
Finder::useClass('TreeControl');

class TreeControlJsTree extends TreeControl
{
	//templates
	protected $template = "tree_control_js_tree.html";
	protected $template_trash_show = "list_simple.html:TrashShow";
	protected $template_trash_hide = "list_simple.html:TrashHide";

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
					$this->load("_parent IN('".implode("','", $parents)."')");
					echo $this->toJSON();
				}
				else
				{
					$nodeParts = explode('-', $nodeId);
					$nodeId = intval($nodeParts[1]);
					$this->load("_parent = ".$nodeId);
									
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
					'current_id' => $this->id,
					'source_url' => RequestInfo::hrefChange(RequestInfo::$baseUrl."do/".$this->config->componentPath, array('action' => 'json', $this->idGetVar => '', 'cid' => $this->id)),
					'update_url' => RequestInfo::hrefChange(RequestInfo::$baseUrl."do/".$this->config->componentPath, array('action' => 'update')),
					'ajax_auto_loading' => $this->config->ajaxAutoLoading,
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

				if (function_exists('json_encode'))
				{
					$treeParams['hide_buttons'] = json_encode($this->config->hide_buttons);
				}
				else
				{
					Finder::useClass('Json');
					$treeParams['hide_buttons'] = Json::encode($this->config->hide_buttons);
				}

				if ($_COOKIE['tree_control_btns'] == 'true')
				{
				 	$treeParams['show_controls'] = true;
				}
				
				if (!$this->config->ajaxAutoLoading)
				{
					$this->load();
				}
				$treeParams['data'] = $this->toJSON();
	

				$this->tpl->set('tree_params', $treeParams);
			break;
		}
	}

	public function getHtml()
	{
		$this->renderTrash();
		return $this->tpl->Parse( $this->template);
	}

	public function toJSON($fromNode = null)
	{
		$this->toRoot = array();
		$c = $this->items[ intval($this->id) ];
		do
		{
			$this->toRoot[$c['id']] = $c['id'];
			$c = $this->items[$c['_parent']] ;
		} while($c);
	
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
				elseif ($this->config->ajaxAutoLoading && ($this->items[$id]['_right'] - $this->items[$id]['_left']) > 1)
				{
					$state = 'closed';
				}
				
				$result[] = array(
					'data' => iconv('cp1251', 'utf-8', $this->_getTitle($this->items[$id])),
					'attributes' => array(
						'id' => 'node-'.$id, 
						'data' => '{type: "'.( ($this->items[$id]['_level'] == 1 && $this->config->denyDropToRoot) ? 'root' : 'node').'"'.($this->items[$id]['_level'] == 1 ? ', max_depth: '.$this->level_limit : '').',path: "'.$this->items[$id]['_path'].'" }',
						'class' => ($this->items[$id]['_state'] == 1 ? 'hidden' : ($this->items[$id]['_state'] == 2 ? 'deleted' : '')),
					),
					'level' => $this->items[$id]['_level'],
					'state' => $state,
					'children' => $this->treeParse($this->children[$id])
				);
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
			UPDATE ??'.$this->config->table_name.' SET _supertag = "" WHERE id = '.$id.'
		');
		return $id;
	}
	
	protected function saveTitle($id, $title)
	{
		$db = &$this->db;
		
		$node = $this->getItem($id);
		
		if ($node['id'])
		{
			$sql = "UPDATE ??".$this->config->table_name." SET title=".$db->quote($title).", title_pre = ".$db->quote($this->tpl->action('typografica', $title));
			
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
				$this->updateTreePathes($this->config->table_name, $node['id'], $this->config->allow_empty_supertag, $this->config->where);
			}
		}
	}
}
?>