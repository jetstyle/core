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
				
//				if ($this->config->ajaxAutoLoading)
//				{
//					if ($_GET['autoload'])
//					{
//						$this->load("_parent = ".$this->id);
//						echo $this->toJSON($this->id);
//					}
//					else
//					{
//						$parents = $this->getParentsForItem($this->id ? $this->id : $this->getRootId());
//						$this->load("_parent IN('".implode("','", $parents)."')");
//						echo $this->toJSON();
//					}
//				}
//				else
//				{
//					$this->load();
//					echo $this->toJSON();
//				}
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
					'source_url' => RequestInfo::hrefChange(RequestInfo::$baseUrl."do/".$this->config->componentPath, array('action' => 'xml')),
					'update_url' => RequestInfo::hrefChange(RequestInfo::$baseUrl."do/".$this->config->componentPath, array('action' => 'update')),
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
					//$this->tpl->set("toggleEditTreeClass", "class='toggleEditTreeClass-Sel'");
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
					$treeParams['data'] = $this->toJSON();
				}

				$this->tpl->set('tree_params', $treeParams);
			break;
		}
	}

	public function getHtml()
	{
		$this->renderTrash();
		return $this->tpl->Parse( $this->template);
	}

	public function toJSON($treeId = 0)
	{
		$this->toRoot = array();
		$c = $this->items[ intval($this->id) ];
		do
		{
			$this->toRoot[$c['id']] = $c['id'];
			$c = $this->items[$c['_parent']] ;
		} while($c);
	
//		$data = array();
		
//		if ($this->config->ajaxAutoLoading)
//		{
//			if (0 == $treeId)
//			{
//				$data = $this->treeParse($this->children[$this->items[$this->getRootId()]['_parent']]);
//			}
//			else
//			{
//				$data = $this->treeParse($this->children[$this->id]);
//			}
//		}
//		else
//		{
			$data = $this->treeParse($this->children[$this->items[$this->getRootId()]['_parent']]);
//		}

		// if ($this->config->fakeRoot)
		// {
		// 	$data = array(
		// 		array(
		// 			'data' => 'Tree root',
		// 			'attributes' => array('id' => 'node-0', 'data' => '{type: "root", max_depth: '.$this->level_limit.'}'),
		// 			'state' => 'open',
		// 			'children' => $data
		// 		),
		// 	);
		// }
		// else
		// {
		//foreach ($data AS &$r)
		//{
		//	$r['attributes']['data'] = '{"max_depth": '.$this->level_limit.', level: 1, type: "'.($this->config->denyDropToRoot ? 'root' : 'node').'"}';
		//}
		// }


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
				$result[] = array(
					'data' => iconv('cp1251', 'utf-8', $this->_getTitle($this->items[$id])),
					'attributes' => array(
						'id' => 'node-'.$id, 
						'data' => '{type: "'.( ($this->items[$id]['_level'] == 1 && $this->config->denyDropToRoot) ? 'root' : 'node').'"'.($this->items[$id]['_level'] == 1 ? ', max_depth: '.$this->level_limit : '').' }',
						'class' => ($this->items[$id]['_state'] == 1 ? 'hidden' : ($this->items[$id]['_state'] == 2 ? 'deleted' : '')),
					),
					'level' => $this->items[$id]['_level'],
					'state' => ((in_array($id, $this->toRoot) && is_array($this->children[$id]) && count($this->children[$id])) > 0 ? 'open' : ''),
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
}
?>