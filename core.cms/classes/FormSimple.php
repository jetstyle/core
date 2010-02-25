<?php

class FormSimple
{
	protected $tpl = null;
	protected $db = null;

	protected $config; //ссылка на объект класса ModuleConfig
	protected $loaded = false; //грузили или нет данные?
	protected $item = null;
	protected $fieldsRendered = false;

	//templates
	protected $template = "form_simple.html";
	protected $template_item = ''; //возьмём из конфига
	protected $_template_item = 'form'; //basename шаблона формы, если брать его из конфига

	protected $prefix="";

	protected $idGetVar = 'id';
	protected $idField = "id";
	protected $id = 0; //id редактируемой записи
	protected $new_id = 0;

	protected $supertagLimit = 20;
	protected $updateSupertagAfterInsert = false;

	protected $html;

	private $model = null;
	private $postData = null;		// данные из формы
	private $postFields = null;

	public function __construct( &$config )
	{
		//base modules binds
		$this->config =& $config;
		$this->tpl = &Locator::get('tpl');
		$this->db = &Locator::get('db');

		if (!isset($this->config['supertag_check']) && !isset($this->config['supertag_path_check']))
		{
			$this->config['supertag_check'] = true;
		}

		$this->prefix = implode('_', $config['module_path_parts']).'_';

		//настройки шаблонов
		$this->store_to = $this->prefix.'tpl';
		if( $config['template_item'] )
		{
			$this->template_item = $config['template_item'];
		}
		if(!$this->template_item)
		{
			$this->template_item = $config['_template_item'] ? $config['_template_item'] : $this->_template_item;
		}

		if ($this->config['idField'])
		{
			$this->idField = $this->config['idField'];
		}

		if ($_GET['insert_fields'])
		{
		    Finder::useClass('Json');
		    $this->insert_fields = Json::decode($_GET['insert_fields'], true);
		}

		$this->id = intval(RequestInfo::get( $this->idGetVar));
	}

	public function setId($id)
	{
		$this->cleanUp();
     	$this->id = $id;
	}

	public function handle()
	{
		$valid = array('text', 'title', 'lead');

		//form in iframe colorbox
		if ( $_GET["popup"] )
		{
			$iframe = array("css_buttons_class"=>"iframe-buttons-");
			Locator::get("tpl")->set( "iframe", $iframe );
		}
		$item = &$this->getItem();
		if ($_GET['ret'] && in_array($_GET['ret'], $valid) )
		{
			header('Content-Type: text/html; charset=windows-1251');
			die( $item[ $_GET['ret'] ] );
		}/*
		elseif ($this->needAjaxUpdate())
		{
			$this->ajax_update = true;
			$this->prefix = "";
		}
		*/
		//update data
		if ($this->needDelete())
		{
			$redirect = $this->delete();
		}
		elseif ($this->needRestore())
		{
			$redirect = $this->restore();
		}
		elseif ($this->needUpdate() || $this->needAjaxUpdate())
		{
			$redirect = $this->update();
		}
		
		if ($this->needAjaxUpdate())
		{
				$postData = $this->getPostData();
				header('Content-Type: text/html; charset=windows-1251');
				
				die($postData[ $_POST['ajax_update'] ]);
		}

		//редирект или выставление флага, что он нужен
		
		if ($redirect)
		{
			$this->_redirect = RequestInfo::hrefChange('', array('rnd' => mt_rand(1,255)));
			
			if ($this->stop_redirect)
			{
				$this->redirect = $this->_redirect;
				return;
			}
			else
			{
				Controller::redirect( $this->_redirect );
			}
		}
	}

	public function getHtml()
	{
		Finder::pushContext();
		Finder::prependDir(Config::get('cms_dir').'modules/'.$this->config['module_name'].'/');

		$tpl = &Locator::get('tpl');
		$tpl->pushContext();

		$tpl->set( 'prefix', $this->prefix );
		$tpl->set( '__form_name', $this->prefix.'_simple_form' );

		if ($this->insert_fields)
		{
				
		    $tpl->set('hidden_fields', $this->insert_fields);
		}

		$item = &$this->getItem();

		if ( $item['id']>0 || RequestInfo::get('_new') )
		{
			    $tpl->set( 'ajax_url', RequestInfo::href() );
		}

		$tpl->set('___form', $this->getFieldsHtml());
		$this->renderButtons();
		$result = $tpl->parse($this->template);

		$tpl->popContext();
		Finder::popContext();

		return $result;
	}

	public function getFieldsHtml()
	{
		Finder::pushContext();
		Finder::prependDir(Config::get('cms_dir').'modules/'.$this->config['module_name'].'/');

		$tpl = &Locator::get('tpl');
		$tpl->pushContext();

		$item = &$this->getItem();

		$tpl->set( 'prefix', $this->prefix );
		$tpl->set( '__form_name', $this->prefix.'_simple_form' );
		$tpl->setRef('*', $item );

		//подготовка нетекстовых полей
		if( !$this->fieldsRendered )
		{
			$this->renderFields();
		}
		
		$result = $tpl->parse( $this->template_item);
		$tpl->popContext();
		Finder::popContext();

		return $result;
	}

	protected function load()
	{
		if( !$this->loaded )
		{
			if($this->id)
			{
				$model = &$this->getModel();
				$model->loadOne('{'.$this->idField.'} = '.$this->id);

				$this->item = $model->getData();
				if (!$this->item[$this->idField])
				{
					if ($this->config['deny_redirects'])
					{
						$this->id = 0;
					}
					else
					{
						Controller::redirect(RequestInfo::hrefChange('', array($this->idGetVar => '')));
					}
				}
			}
			$this->loaded = true;
			return true;
		}
		return false;
	}
	
	public function &getItem()
	{
		$this->load();
		return $this->item;
	}

	public function update($updateData = null)
	{
		$postData = $this->getPostData();

		if (is_array($updateData))
		{
			$postData = array_merge($postData, $updateData);
		}

		$this->filters($postData);

		if ($this->id)
		{
			$this->updateData($postData);
			return true;
		}
		elseif (!$this->config['dont_insert'])
		{
			$this->insert($postData);
			return true;
		}


		return false;
	}

	public function delete()
	{
		if ($this->id)
		{
			$model = &$this->getModel();
			return $model->deleteToTrash($this->id);
		}
		else
		{
			return false;
		}
	}

	public function restore()
	{
		if ($this->id)
		{
			$model = &$this->getModel();
			$model->restoreFromTrash($this->id);
			return true;
		}
		else
		{
			return false;
		}
	}

	public function needAjaxUpdate()
	{
		return $_POST["ajax_update"] ? true : false;
	}

	public function needUpdate()
	{
		return $_POST[$this->prefix."update"] ? true : false;
	}

	public function needDelete()
	{
        return $_POST[($this->needAjaxUpdate() ? '' : $this->prefix)."delete"] ? true : false;
	}

	public function needRestore()
	{
		return $_POST[$this->prefix."restore"] ? true : false;
	}

	protected function insert($postData)
	{
		$postData['_created'] = date('Y-m-d H:i:s');

		$this->filters($postData);

		$model = &$this->getModel();
		$this->new_id = $model->insert($postData);
		$this->setId($this->new_id);

		// update order
		$data = array('_order' => $this->id);

		

		if ($this->updateSupertagAfterInsert)
		{
			$data['_supertag'] = $insertData['_supertag'].'_'.$this->id;
		}
		$this->updateData($data);
		RequestInfo::set($this->idGetVar, $this->id);
	}

	protected function cleanUp()
	{
		$this->new_id = 0;
		$this->loaded = false;
		$this->model = null;
		$this->item = null;
		$this->postData = null;
		$this->postFields = null;
		$this->updateSupertagAfterInsert = false;
		$this->html = '';
		$this->fieldsRendered = false;
	}

	protected function renderButtons()
	{
		$tpl = &Locator::get('tpl');
		$item = &$this->getItem();

		if($this->delete_title)
		{
			$tpl->set('__delete_title', $this->delete_title);
		}
		else
		{
			$tpl->set( '__delete_title', $item['_state'] !=2 ? 'удалить в корзину' : 'удалить окончательно'  );
		}

		if($this->id && !$this->config['hide_delete_button'] )
		{
			$tpl->parse( $this->template.':delete_button', '_delete_button' );
		}

		if(!$this->config['hide_save_button'] )
		{
			if ($_GET['popup']==1)
			    $tpl->set('popup', 1);

			if($item['_state'] == 2)
			{
				$tpl->parse( $this->template.':restore_button', '_save_button' );
			}
			else
			{
				$tpl->parse( $this->template.':save_button'.( $this->config['ajax_save'] ? '_norefresh' : ''), '_save_button' );
			}
		}

		if($this->config['send_button'] && $this->id  && $item['_state'] == 0)
		{
			if($item['sended'])
			{
				$tpl->parse( $this->template.':send_button_disabled', '_send_button' );
			}
			else
			{
				$tpl->parse( $this->template.':send_button', '_send_button' );
			}
		}
	}

	protected function &getModel()
	{
		if (null === $this->model)
		{
            $this->model = $this->constructModel();
		}

		return $this->model;
	}

    protected function constructModel()
    {
		if (!$this->config['model'])
        {
            throw new JSException("You should set `model` param in config");
        }

        Finder::useModel('DBModel');
        $model = DBModel::factory($this->config['model']);

		foreach($model->getForeignFields() AS $field => $conf)
		{
			if ($conf['type'] == 'has_one' && $conf['pk'])
			{
				$model->addField($conf['pk']);
			}
		}

        return $model;
    }

	protected function renderFields()
	{
		$this->fieldsRendered = true;

		$this->handleForeignFields();

		$tpl =& $this->tpl;
		$item = &$this->getItem();

		if ($item['_state']>0)
		{
			$this->tpl->set("body_class", "class='state1'");
		}

		/*
			 $this->config->RENDER - каждая запись в нём:
			 0 - имя поля
			 1 - тип поля - checkbox | select | radiobutton
			 2 - хэш значений - array( id => value )
		 */

		if( is_array($this->config['render']) )
		{
                    if (!empty($this->config['render']['checkbox']))
                    {
                        foreach ($this->config['render']['checkbox'] as $checkbox)
                        {
                            $tpl->set( 'checkbox_'.$checkbox, $item[$checkbox] ? "checked=\"checked\"" : '' );
                        }
                    }
                    if (!empty($this->config['render']['select']))
                    {
                        foreach ($this->config['render']['select'] as $name => $params)
                        {
                            $str = '';
                            foreach($params['values'] as $id => $val)
                            {
                                    $str .= "<option value='".$id."' ".(($item["id"] && $item[$name]==$id) || (!$item["id"] && $id==$params['default']) ? "selected=\"selected\"" : '' ).">".$val;
                            }
                            $tpl->set( 'options_'.$name, $str );
                        }
                    }
		}

		return true;
	}

	protected function handleForeignFields()
	{
		$model = &$this->getModel();

		foreach($model->getForeignFields() AS $fieldName => $conf)
                {
                    if (is_array($conf) && $conf['type'] == 'has_one')
                    {
                        $foreignModel = clone $model->getForeignModel($fieldName);

                        $conf = $model->getForeignFieldConf($fieldName);
                        $model->addField($conf['pk']);
                        $foreignModel->load();
                        $data = array();
                        foreach($foreignModel AS $r)
                        {
                            if (!isset($r["_state"]) || $r["_state"]==0)
                                $data[$r[$conf['fk']]] = ($r['_level'] ? str_repeat("&nbsp;&nbsp;", $r['_level']-1) : ""). $r['title'];
                        }

                        $this->config['render']['select'][$conf['pk']] = array(
                            'values' => $data,
                            'default' => $conf['default'],
                        );
                    }
                }
	}

	protected function getPostFields()
	{
		if ($this->postFields === null)
		{
			$this->postFields = $this->constructPostFields();
		}

		return $this->postFields;
	}

	protected function constructPostFields()
	{
		$model = &$this->getModel();
		$result = $model->getAllFields();
		return $result;
	}

	protected function &getPostData()
	{
		if ($this->postData === null)
		{
			$this->postData = $this->constructPostData();
		}

		return $this->postData;
	}

	protected function constructPostData()
	{
		$postData = array();

		$fields = $this->getPostFields();
		
		foreach ($fields AS $fieldName)
		{
			if ($fieldName !== $this->idField)
			{
				if (null !== $_POST[$this->prefix.$fieldName])
				{
					$postData[$fieldName] = $_POST[$this->prefix.$fieldName];
					if ($this->needAjaxUpdate())
					{
						$postData[$fieldName] = iconv('UTF-8', 'CP1251', $postData[$fieldName]);
					}
					RequestInfo::free($this->prefix.$fieldName);
				}
			}
		}

		if (!$this->needAjaxUpdate())
		{
			if (is_array($this->config['render']['checkbox']))
			{
				foreach ($this->config['render']['checkbox'] AS $fieldName)
				{
					if (!$postData[$fieldName]) $postData[$fieldName] = 0;
				}
			}
		}

		return $postData;
	}

	protected function updateData($data)
	{
		$model = &$this->getModel();
		$model->update( $data, '{'.$this->idField.'} = '.DBModel::quote($this->id) );
	}

	protected function filters(&$postData)
	{
		$tpl = &$this->tpl;

		//filter data
		if( is_array($this->config['update_filters']) )
		{
			foreach( $this->config['update_filters'] AS $filter => $fields )
			{
				if (is_array($fields))
				{
					foreach ($fields AS $field)
					{
						if (isset($postData[ $field ]))
						{
							$postData[ $field ] = $tpl->action( $filter, $postData[ $field ] );
						}
					}
				}
			}
		}

		//pre-rendering
		if ( is_array($this->config['pre_filters']) )
		{
			foreach ( $this->config['pre_filters'] AS $filter => $fields )
			{
				if (is_array($fields))
				{
					foreach ($fields AS $field)
					{
						if ( isset($postData[ $field ]) )
						{
							$field_pre = $field.'_pre';
							if (!isset($postData[ $field_pre ]))
							{
								$postData[ $field_pre ] = $postData[ $field ];
							}

							$postData[ $field_pre ] = $tpl->action( $filter, $postData[ $field_pre ]);
						}
					}
				}
			}
		}
		if ( $this->needAjaxUpdate() )
		    return;

		//supertag
		if ( $this->config['supertag'])
		{
			if ( is_array($this->config['supertag']) )
			{
				$field = $this->config['supertag'][0];
				$limit = $this->config['supertag'][1];
			}
			else
			{
				$field = $this->config['supertag'];
				$limit = $this->supertagLimit;
			}

			if ($_POST[$this->prefix . '_supertag'] === '')
			{
				Finder::useClass('Translit');
				$translit = new Translit();
				$postData['_supertag'] = $translit->supertag( $postData[$field], TR_NO_SLASHES, $limit );
			}

			if ($this->config['supertag_check'] || $this->config['supertag_path_check'])
			{
				$where = "_supertag=".$this->db->quote($postData['_supertag'])." AND id <> ".intval($this->id);

				if ($this->config['supertag_path_check'])
				{
                    $item = DBModel::factory($this->config['model'])->setFields(array('_parent'))->loadOne('{id} = '.intval($this->id));
					$where .= ' AND _parent = '.intval($item['_parent']);
				}

				$rs = DBModel::factory($this->config['model'])->setFields(array('id', '_supertag'))->loadOne($where);
				if ($rs['id'])
				{
					if (!$this->id)
					{
						$this->updateSupertagAfterInsert = true;
					}
					else
					{
						$postData['_supertag'] .= '_'.$this->id;
					}
				}
			}
		}
	}


	// @TODO: it's a bad way to do this
	// @TODO: bad way is better then no way
	protected function getList()
	{
		$module = Locator::get('controller')->moduleConstructor;
		$children = $module->getChildren();
		$config = $module->getConfig();
		if ($children && $children['list'])
		{
			return $module->getList()->getObj();
		}
		else
		{
			$path = $config['module_path_parts'][0].'/'.$config['module_path_parts'][1].'/list';
			$list = ModuleConstructor::factory($path);
			return $list->getObj();
		}
	}	
}
?>