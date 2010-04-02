<?php
Finder::useClass('TreeControl');

class TreeSimple extends ListSimple  implements ModuleInterface
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
            $parentsIds = array();
            foreach($parents AS $parent)
            {
                $parentsIds[] = str_replace('node-', '', $parent);
            }

            $where = empty($parentsIds) ? '' : "_parent IN (".DBModel::quote($parentsIds).")";
                
            $this->load($where);
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

            Finder::useModel('DBModelTree');
            $model = DBModelTree::factory($this->config['model']);
            $model->addFields(array('_order', '_state'));

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

	public function insert($node=array())
	{
		
	}
        
        public function update()
	{
		
	}

}
?>
