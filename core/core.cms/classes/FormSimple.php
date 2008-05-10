<?php

class FormSimple 
{
	protected $rh; //ссылка на $rh
	protected $config; //ссылка на объект класса ModuleConfig
	protected $loaded = false; //грузили или нет данные?
	protected $model = null;
	protected $item = null;
	protected $postData = array();		// данные из формы
	
	//templates
	protected $template = "form_simple.html";
	protected $template_item = ''; //возьмём из конфига
	protected $_template_item = 'form'; //basename шаблона формы, если брать его из конфига

	protected $prefix;
	
	protected $idGetVar = 'id';
	protected $idField = "id";
	protected $id = 0; //id редактируемой записи

	protected $supertagLimit = 20;
	
	protected $html;
	
	public function __construct( &$config )
	{
		//base modules binds
		$this->config =& $config;
		$this->rh = &$config->rh;
		
		if (is_array($this->config->has_one))
		{
			foreach($this->config->has_one AS $value)
			{
				$config->SELECT_FIELDS[] = $value['fk'];
			}
		}
		$config->SELECT_FIELDS[] = '_state';
		
		if (!$this->config->UPDATE_FIELDS)
		{
			$this->config->UPDATE_FIELDS = $this->config->SELECT_FIELDS;
		}
		
		$this->rh->useModel('DBModel');
		$this->model = new DBModel();
		$this->model->setTable($config->table_name);
		$this->model->setFields($config->SELECT_FIELDS);
		$this->model->initialize($this->rh);
		
		$this->prefix = $config->moduleName.'_form_';
		
		//настройки шаблонов
		$this->store_to = "form_".$config->getModuleName();

		if( $config->template_item )
		{
			$this->template_item = $config->template_item;
		}

		if(!$this->template_item)
		{
			$this->template_item = $config->_template_item ? $config->_template_item : $this->_template_item;
		}
		
		if ($this->config->idField)
		{
			$this->idField = $this->config->idField;
		}
		
		$this->id = intval($this->rh->ri->get( $this->idGetVar));
	}
	
	public function handle()
	{
		//load data
		$this->load();

		//update data 
		if ($this->needDelete())
		{
			$redirect = $this->delete();
		}
		elseif ($this->needRestore())
		{
			$redirect = $this->restore();
		}
		elseif ($this->needUpdate())
		{
			$this->readPost();
			$redirect = $this->update();
		}
		
		//редирект или выставление флага, что он нужен
		if ($redirect)
		{
			$this->_redirect = $this->rh->ri->hrefPlus('', array('rnd' => mt_rand(1,255)));
			if ($this->stop_redirect)
			{
				$this->redirect = $this->_redirect;
				return;
			}
			else
			{
				$this->rh->redirect( $this->_redirect );
			}
		}

		$tpl =& $this->rh->tpl;

		//подготовка нетекстовых полей
		$this->renderFields();

		//render form
		$tpl->setRef( '*', $this->item );

		$tpl->set( 'prefix', $this->prefix );
		$tpl->set( '__form_name', $this->prefix.'_simple_form' );
		if($this->delete_title)
		{
			$tpl->set('__delete_title', $this->delete_title);
		}
		else
		{
			$tpl->set( '__delete_title', $this->item['_state'] !=2 ? 'удалить в корзину' : 'удалить окончательно'  );
		}

		if($this->id && !$this->config->hide_delete_button )
		{
			$tpl->parse( $this->template.':delete_button', '_delete_button' );
		}

		if(!$this->config->hide_save_button )
		{
			if($this->item['_state'] == 2)
			{
				$tpl->parse( $this->template.':restore_button', '_save_button' );
			}
			else
			{
				$tpl->parse( $this->template.':save_button', '_save_button' );
			}
		}

		if($this->config->send_button && $this->id  && $this->item['_state'] == 0)
		{
			if($this->item['sended'])
			{
				$tpl->parse( $this->template.':send_button_disabled', '_send_button' );
			}
			else
			{
				$tpl->parse( $this->template.':send_button', '_send_button' );
			}
		}
	}

	public function getHtml()
	{
		$this->rh->tpl->parse( $this->template_item, '___form');
		return $this->rh->tpl->parse($this->template);
	}
	
	protected function load()
	{
		if( !$this->loaded )
		{
			if($this->id)
			{
				$this->model->load($this->model->quoteField($this->idField).'='.$this->id);
				list($this->item) = $this->model->getData();
				
				if (!$this->item[$this->idField])
				{
					$this->rh->redirect($this->rh->ri->hrefPlus('', array($this->idGetVar => '')));
				}
				
				if ($this->item['_state']>0)
				{
					$this->rh->tpl->set("body_class", "class='state1'");
				}
			}
			$this->loaded = true;
			return true;
		}
		return false;
	}
	
	protected function renderFields()
	{
		if( $this->fieldsRendered ) 
		{
			return;
		}
		
		$this->fieldsRendered = true;

		$this->handleForeignFields();

		$tpl =& $this->rh->tpl;
		
		/*
			 $this->config->RENDER - каждая запись в нём:
			 0 - имя поля
			 1 - тип поля - checkbox | select | radiobutton
			 2 - хэш значений - array( id => value )
		 */
		
		if( is_array($this->config->RENDER) )
		{
			$N = count($this->config->RENDER);
			for($i=0;$i<$N;$i++){
				$row =& $this->config->RENDER[$i];
				switch( $row[1] ){
					case 'checkbox':
						$tpl->set( 'checkbox_'.$row[0], $this->item[$row[0]] ? "checked=\"checked\"" : '' );
						break;
					case 'select':
						$_str = '';
						$A =& $row[2];
						foreach($row[2] as $_id=>$_val)
						{
							//            modified by geronimo
							//              $_str .= "<option value='".$_id."' ".( $this->item[$row[0]]==$_id  || (!$this->item["id"] && $_id==$row[3]) ? "selected=\"selected\"" : '' ).">".$_val;
							$_str .= "<option value='".$_id."' ".(($this->item["id"] && $this->item[$row[0]]==$_id) || (!$this->item["id"] && $_id==$row[3]) ? "selected=\"selected\"" : '' ).">".$_val;
						}
						$tpl->set( 'options_'.$row[0], $_str );
						break;
					case 'radiobutton':
						//заполним по мере необходимости
						break;
				}
			}
		}
	}

	protected function handleForeignFields()
	{
		if (!isset($this->config->has_one) || !is_array($this->config->has_one))
		{
			return;
		}
		
		foreach($this->config->has_one AS $key => $value)
		{
			$value['pk']  = $value['pk'] ? $value['pk'] : "id";
			
			// пытаемся найти модель 
			if ($modelFile = $this->rh->findScript('classes/models', $value['name']))
			{
				$this->rh->useModel($value['name']);
				$model = new $value['name']();
				//@todo: hack
				$model->fields = array($value['pk'], 'title');
				$model->fields_info = array();
				$model->has_many = array();
				
				$model->order = array("title");
				
				$model->initialize($this->rh);
				$model->load();
				
				$data = array();
				foreach($model AS $r)
				{
					$data[$r[$value['pk']]] = $r['title'];
				}
				
				$this->config->RENDER[] = array($value['fk'], "select", $data);
			}
			// думаем, что $value['name'] это имя таблицы
			else
			{				
				$result = $this->rh->db->execute("
					SELECT ".$value['pk'].", title
					FROM ".$this->rh->db_prefix.$value['name']."
					WHERE _state = 0
					ORDER BY ".($value['order'] ? $value['order'] : "title ASC")."
				");
				
				if ($result)
				{
					$data = array();
					while ($r = $this->rh->db->getRow($result))
					{
						$data[$r[$value['pk']]] = $r['title'];
					}
					
					$this->config->RENDER[] = array($value['fk'], "select", $data);
				}
			}
		}
	}
	
	protected function delete()
	{
		if ($this->item['_state'] <= 1 )
		{
			$this->updateData(array('_state' => 2));
			return 1;
		}
		// удаляем насовсем
		else
		{
			$this->model->delete($this->model->quoteFieldShort($this->idField).'='.$this->id);
			$this->rh->ri->free($this->idGetVar);
			return 2;
		}
	}

	protected function restore()
	{
		$this->updateData(array('_state' => 0));
		return true;
	}

	protected function readPost()
	{
		if (empty($this->postData))
		{
			foreach ($this->config->UPDATE_FIELDS AS $fieldName)
			{
				if ($fieldName !== $this->idField)
				{
					if (null === $_POST[$this->prefix.$fieldName])
					{
						$_POST[$this->prefix.$fieldName] = '';
					}
					$this->postData[$fieldName] = $_POST[$this->prefix.$fieldName];
					$this->rh->ri->free($this->prefix.$fieldName);
				}
			}
		}
	}
		
	protected function update()
	{
		$this->filters();
			
		if ( $this->id )
		{
			$this->updateData($this->postData);
		}
		elseif (!$this->config->dont_insert)
		{
			$this->insert();
			$this->rh->ri->set($this->idGetVar, $this->id);
		}
		
		return true;
	}
	
	protected function updateData($data)
	{
		$this->model->update( $data, $this->model->quoteFieldShort($this->idField).'='.$this->model->quote($this->id) );
	}

	protected function needUpdate()
	{
		return $_POST[$this->prefix."update"] ? true : false;
	}

	protected function needDelete()
	{
		return $_POST[$this->prefix."delete"] ? true : false;
	}
	
	protected function needRestore()
	{
		return $_POST[$this->prefix."restore"] ? true : false;
	}
	
	protected function filters()
	{
		$rh =& $this->rh;
		$tpl =& $rh->tpl;
		
		//filter data
		if( is_array($this->config->UPDATE_FILTERS) )
		{
			foreach( $this->config->UPDATE_FILTERS AS $field => $filter )
			{
				if( is_string($field) )
				{
					//some field specified
					if (isset($this->postData[ $field ]))
					{
						$this->postData[ $field ] = $tpl->action( $filter, $this->postData[ $fieldName ] );
					}
				}
				else
				{
					//filter all fields
					$m = count($this->UPDATE_FIELDS);
					for ($j=0; $j < $m; $j++)
					{
						$fieldName = $this->UPDATE_FIELDS[$j];
						if (isset($this->postData[ $fieldName ]))
						{
							$this->postData[ $fieldName ] = $tpl->action( $filter, $this->postData[ $fieldName ] );
						}
					}
				}
			}
		}

		//pre-rendering
		if ( is_array($this->config->PRE_FILTERS) )
		{
			foreach ( $this->config->PRE_FILTERS AS $filter => $fields )
			{
				foreach ($fields AS $field)
				{
					$field_pre = $field.'_pre';
					if (!isset($this->postData[ $field_pre ]))
					{
						$this->postData[ $field_pre ] = $this->postData[ $field ];
					}
					$this->postData[ $field_pre ] = $tpl->action( $filter, $this->postData[ $field_pre ]);
					//добавляем поле в список для сохранения
					$this->UPDATE_FIELDS[] = $field_pre;
				}
			}
		}

		//supertag
		if ( $this->config->supertag )
		{
			if ( is_array($this->config->supertag) )
			{
				$field = $this->config->supertag[0];
				$limit = $this->config->supertag[1];
			}
			else
			{
				$field = $this->config->supertag;
				$limit = $this->supertagLimit;
			}
			
			$rh->useClass('Translit');
			$translit =& new Translit();
			$this->postData['_supertag'] = $translit->translateLink( $this->postData[$field], $limit );
			if ($this->config->supertag_check)
			{
				$sql = "SELECT id, _supertag FROM ??".$this->config->table_name." WHERE _supertag=".$rh->db->quote($this->postData['_supertag']);
				$rs = $rh->db->queryOne($sql);
				if ($rs['id'])
				{
					$this->postData['_supertag'] .= "_".$this->id;
				}
			}
			$this->UPDATE_FIELDS[] = '_supertag';
		}
		elseif ($this->config->allow_empty_supertag)
		{	 
			$this->UPDATE_FIELDS[] = '_supertag';
		}
	}

	protected function insert()
	{
		if (is_array($this->config->INSERT_FIELDS) && !empty($this->config->INSERT_FIELDS))
		{
			foreach ($this->config->INSERT_FIELDS AS $fieldName => $value)
			{
				$this->postData[$fieldName] = $value;
			}
		}

		$this->postData['_created'] = date('Y-m-d H:i:s');
		$this->new_id = $this->id = $this->model->insert($this->postData);
		
		// update order
		$this->updateData(array('_order' => $id));		
	}
}

?>