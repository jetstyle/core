<?php
Finder::useClass('ListSimple');
Finder::useModel('DBModelTree');

class TreeSimple extends ListSimple  implements ModuleInterface
{
	protected $children;

	//templates
	protected $template = "tree_control_js_tree.html";
	protected $template_trash_show = "list_simple.html:trash_show";
	protected $template_trash_hide = "list_simple.html:trash_hide";

	protected $rootId = 0;
	protected $toRoot = array();

    public function __construct( &$config )
	{
		if (!$config['hide_buttons']) $config['hide_buttons'] = array();
                
        if (!isset($config['level_limit']))
        {
            $config['level_limit'] = 3;
        }

        if (!isset($config['redirectIfEmptyId']))
        {
            $config['redirectIfEmptyId'] = false;
        }
        
		if (!isset($config['hide_buttons']['addChild']))
		{
			$config['hide_buttons']['addChild'][$config['level_limit']] = true;
		}

        parent::__construct($config);
               
	}

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
				header("Content-Type: application/json");

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

				if (!$this->id && $this->config['redirectIfEmptyId'])
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
                // var_dump($treeParams);
                // var_dump($treeParams);

				$url = $this->config["go_url"] ? $this->config["go_url"] : RequestInfo::hrefChange('', array('id' => ''));
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

                                if ( $this->config['edit_module'] )
                                    $treeParams['edit_url'] = RequestInfo::$baseUrl."do/".$this->config['edit_module'];// ? $this->config['edit_url'] : $treeParams['go_url'] ;

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
				$treeParams['level_limit'] = $this->config['level_limit'];
				$treeParams['disable_drag'] = $this->config['disable_drag'] ? true : false;

                                //var_dump($treeParams);
                                //die();
				$this->tpl->set('tree_params', $treeParams);
				break;
		}
	}

	protected function getParentsForItem($id)
	{
		return $this->getModel()->getParentsIds($id);
	}
	
	protected function getRootId()
	{
		if (!$this->rootId)
		{
			$model = clone $this->getModel();
			//echo $model->getOrderSql();
			//$model->cleanUp();
			//$model->setOrder(array('_state' => 'ASC', '_order' => 'ASC'));
			$model->loadOne("{_parent} = 0 AND {_state} IN (0,1,2)");			
						
			if ($model[$model->getPk()])
			{
				$this->rootId = $model[$model->getPk()];
			}
		}
		return $this->rootId;
	}

	protected function loadByParents($parents)
	{
		$parentsIds = array();
		foreach($parents AS $parent)
		{
			$parentsIds[] = str_replace('node-', '', $parent);
		}

		$where = empty($parentsIds) ? '' : "_parent IN (".DBModel::quote($parentsIds).")";

		$this->load($where);
	}

	protected function createRootNode()
	{
		$_REQUEST['newtitle'] = iconv('cp1251', 'UTF-8', 'Новый узел');
		$id = $this->insert();

		if ($this->config['denyDropToRoot'])
		{
			$updateData = array('_supertag' => '', '_path' => '');
			$this->getModel()->updateNode($id, $updateData);
		}

	}

	protected function getNodesCountForRoot()
	{
		$data = array('total' => 0, 'deleted' => 0);
		
		$model = clone $this->getModel();
		
		$model->loadPlain("{_parent} = 0 AND {_state} >=0 ");
			
		foreach ($model AS $r)
		{
			$data['total']++;
			if ($r['_state'] == 2)
			{
				$data['deleted']++;
			}
		}

		return $data;
	}
	

	public function getHtml()
	{
		$this->renderFilters();
		$this->renderTrash();
		return $this->tpl->Parse( $this->template);
	}


	public function load($where="")
	{
		if (! $this->loaded)
		{

			$model = $this->getModel();

			$model->load($where);

			$data  = $model->getData();
			//var_dump($data);
			if ( !empty($data) )
			{
				$this->items = $model->getItems();
				$this->children = $model->getChildren();

                            /*
                            foreach ($data as $r)
                            {
                                    $r['has_children'] = $r['_right'] - $r['_left'] > 1;
                                    $this->items[$r[$this->idField]] = $r;
                                    $this->children[$r['_parent']][] = $r[$this->idField];
                            }
                            */

				$this->loaded = true;
			}
		}
	}

	protected function constructModel()
	{

		if (!$this->config['model'])
		{
			throw new JSException("You should set `model` param in config");
		}
		
		$model = DBModelTree::factory($this->config['model']);
		$model->addFields(array('_order', '_state', 'has_children' => '(({_right} - {_left}) > 1)'));
		$model->setOrder('_left ASC, _order ASC');

		$model->where .= ($model->where ? " AND " : "" ).($_GET['_show_trash'] ? '{_state}>=0' : "{_state} <>2 ");

		return $model;
	}

	protected function &getModel()
	{
		if (null === $this->model)
		{
			$this->model = $this->constructModel();
			$this->applyFilters($this->model);
		}

		return $this->model;
	}
	
	protected function updateTreeStruct()
	{
		if ($_REQUEST['rename'])
		{
			$id = $_REQUEST['id'];
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
			$id = $this->insert();
			return $id;
		}
		elseif($delete = $_REQUEST['delete'])
		{
			$this->deleteNode($delete);
			return '1';
		}
		elseif($_REQUEST['change'])
		{
			$itemId = intval($_REQUEST['id']);
			$targetId = intval($_REQUEST['target']);
			$beforeId = intval($_REQUEST['before']);

			$db = &$this->db;

			if($beforeId)
			{
				$ret = $this->getModel()->moveNodeBefore($itemId, $beforeId);
			}
			else
			{
				$ret = $this->getModel()->moveNodeInto($itemId, $targetId);
			}

			return $ret;
		}
		return '0';
	}
	
	protected function getItem($id)
	{
		$node = clone $this->getModel();
		// @TODO: dirty hack, i'm not proud of it
		$node->where = preg_replace('/{_state}\s*(=|>=|>|<=|<|<>)\s*\d+\s*(AND)?/', '', $node->where);
		$node->loadOne('{'.$node->getPk().'} = '.DBModel::quote($id));
		
		return $node;
	}
	
	protected function saveTitle($id, $title)
	{
		$node = clone $this->getModel();
		$node->loadOne('{'.$node->getPk().'} = '.DBModel::quote($id));

		if ($node[$node->getPk()])
		{
			$updateData = array(
				'title' => $title,
				'title_pre' => Locator::get('tpl')->action('typografica', $title),	
			);
			
			if (!$node['_supertag'])
			{
				Finder::useClass('Translit');
				$translit = new Translit();
				$updateData['_supertag'] = $translit->supertag($title, 20);
			}
						
			$node->update($updateData, '{'.$node->getPk().'} = '.DBModel::quote($id));
			
			if ($updateData['_supertag'])
			{
				$node->rebuild();
			}
		}
	}
	
	protected function toJSON($fromNode = null)
	{
		$this->toRoot = array();
		$c = $this->items[ $this->id ];
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
	
	public function insert($node=array())
	{
		if (!$node['title']) $node['title'] = iconv("UTF-8", "CP1251", $_REQUEST['newtitle']);
		if(strlen($node['title']) == 0)
		{
			$node['title'] = 'Новый узел';
		}

		$node['title_pre'] = $this->tpl->action('typografica', $node['title']);
		if (!$node['_parent'] && $_REQUEST['parent'])
		{
			$node['_parent'] = intval($_REQUEST['parent']);
		}

		if (isset($this->config['insert']) && is_array($this->config['insert']))
		{
			foreach ($this->config['insert'] AS $fieldName => $fieldValue)
			{
				$node[$fieldName] = $fieldValue;
			}
		}
				
		if ($node['before'])
		{
			$before = $node['before'];
			unset($node['before']);
			return $this->getModel()->insertBefore($before, $node);
		}
		elseif ($before = intval($_REQUEST['before']))
		{
			return $this->getModel()->insertBefore($before, $node);
		}
		else
		{
			return $this->getModel()->insert($node);
		}		
	}

	public function update()
	{

	}

	public function getChildren()
	{
		return $this->children;
	}
	
	public function deleteNode($nodeId)
	{
		$node = $this->getItem($nodeId);

		if ($node[$node->getPk()])
		{
			if ($node['_state'] == 2)
			{
				$this->getModel()->deleteNode($nodeId);
			}
			else
			{
				$this->getModel()->deleteNodeToTrash($nodeId);
			}	
		}
	}

}
?>
