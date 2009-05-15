<?php

class FormSimple
{
	protected $tpl = null;
	protected $db = null;

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
	protected $updateSupertagAfterInsert = false;

	protected $html;

	public function __construct( &$config )
	{
		//base modules binds
		$this->config =& $config;
		$this->tpl = &Locator::get('tpl');
		$this->db = &Locator::get('db');

		if (is_array($this->config->has_one))
		{
			foreach($this->config->has_one AS $value)
			{
				$config->SELECT_FIELDS[] = $value['pk'];
			}
		}
		$config->SELECT_FIELDS[] = '_state';

		if (!$this->config->UPDATE_FIELDS)
		{
			$this->config->UPDATE_FIELDS = $this->config->SELECT_FIELDS;
		}

		if (!isset($this->config->supertag_check) && !isset($this->config->supertag_path_check))
		{
			$this->config->supertag_check = true;
		}

		$this->initModel();

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

		$this->id = intval(RequestInfo::get( $this->idGetVar));
	}

	public function handle()
	{
		//load data
		$this->load();

		$valid = array('text', 'title', 'lead');

		//form in iframe thickbox
		if ( $_GET["popup"] )
		{

			$iframe = array("css_buttons_class"=>"iframe-buttons-",
					"height"=>( $_GET["height"]>0 ? ($_GET["height"]-80)."px" : "360px") );//thickbox default height(440) - buttons heoght
			Locator::get("tpl")->set( "iframe", $iframe );
		}


		if ($_GET['ret'] && in_array($_GET['ret'], $valid) )
		{
		    header('Content-Type: text/html; charset=windows-1251');
		    die( $this->item[ $_GET['ret'] ] );
		}
		else if ($this->needAjaxUpdate())
		{
			$this->ajax_update = true;

			$this->prefix = "";

			//var_dump( $this->config->UPDATE_FIELDS, $_POST, array_intersect_key($_POST, array_flip($this->config->UPDATE_FIELDS)) );
			//die();
			//$this->ajaxValidFields;

			$this->config->UPDATE_FIELDS = array_flip( array_intersect_key($_POST, array_flip( $this->config->UPDATE_FIELDS ) ));

			$this->readPost();
			//var_dump( $this->prefix, $this->postData ) ;

			$redirect = $this->update();
			$this->loaded=false;
			//$this->load();

			header('Content-Type: text/html; charset=windows-1251');
			die($this->postData[ $_POST['ajax_update'] ]);
		}
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

		$tpl =& $this->tpl;

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
			if ($_GET['popup']==1)
			    $tpl->set('popup', 1);

			if($this->item['_state'] == 2)
			{
				$tpl->parse( $this->template.':restore_button', '_save_button' );
			}
			else
			{
				$tpl->parse( $this->template.':save_button'.( $this->config->ajax_save ? '_norefresh' : ''), '_save_button' );
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


		if ( $this->item['id']>0 )
		    $tpl->set( 'ajax_url', RequestInfo::href() );
	}

	public function getHtml()
	{
		$this->tpl->parse( $this->template_item, '___form');
		return $this->tpl->parse($this->template);
	}

	protected function initModel()
	{
		Finder::useModel('DBModel');
		$this->model = new DBModel();
		$this->model->setTable($this->getTableName());
		$this->model->setFields($this->config->SELECT_FIELDS);
	}

	public function getTableName()
	{
		if (!$this->config->table_name)
		{
			Finder::useClass('Inflector');
			$pathParts = explode('/', $this->config->componentPath);
			array_pop($pathParts);
			$pathParts = array_map(array(Inflector, 'underscore'), $pathParts);
			$this->config->table_name = strtolower(implode('_', $pathParts));
		}

		return $this->config->table_name;
	}

	public function load()
	{
		if( !$this->loaded )
		{
			if($this->id)
			{
				$this->model->loadOne($this->model->quoteField($this->idField).'='.$this->id);
				$this->item = $this->model->getData();
				if (!$this->item[$this->idField])
				{
					Controller::redirect(RequestInfo::hrefChange('', array($this->idGetVar => '')));
				}

				if ($this->item['_state']>0)
				{
					$this->tpl->set("body_class", "class='state1'");
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

		$tpl =& $this->tpl;

		/*
			 $this->config->RENDER - каждая запись в нём:
			 0 - имя поля
			 1 - тип поля - checkbox | select | radiobutton
			 2 - хэш значений - array( id => value )
		 */

		if( is_array($this->config->RENDER) )
		{
			$N = count($this->config->RENDER);
			for($i=0;$i<$N;$i++)
			{
				$row =& $this->config->RENDER[$i];
				switch( $row[1] )
				{
					case 'checkbox':
						$tpl->set( 'checkbox_'.$row[0], $this->item[$row[0]] ? "checked=\"checked\"" : '' );
					break;

					case 'select':
						$_str = '';
						$A =& $row[2];
						foreach($row[2] as $_id=>$_val)
						{
							$_str .= "<option value='".$_id."' ".(($this->item["id"] && $this->item[$row[0]]==$_id) || (!$this->item["id"] && $_id==$row[3]) ? "selected=\"selected\"" : '' ).">".$_val;
						}
						$tpl->set( 'options_'.$row[0], $_str );
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
			$value['fk']  = $value['fk'] ? $value['fk'] : "id";

			// пытаемся найти модель
			try
			{
				$model = DBModel::factory($value['name']);
			}
			catch(Exception $e)
			{
				//
			}

			if ($model)
			{
				$model->setFields(array($value['fk'], 'title'));
				$model->setOrder(array("title" => "ASC"));
				if ($value['where'])
				{
					$model->where = ($model->where ? $model->where." AND " : "").$value['where'];
				}
				$model->load();

				$data = array();
				foreach($model AS $r)
				{
					$data[$r[$value['fk']]] = $r['title'];
				}

				$this->config->RENDER[] = array($value['pk'], "select", $data, $value['default']);
			}
			// думаем, что $value['name'] это имя таблицы
			else
			{
				$result = $this->db->execute("
					SELECT ".$value['fk'].", title
					FROM ??".$value['name']."
					WHERE _state = 0
					ORDER BY ".($value['order'] ? $value['order'] : "title ASC")."
				");

				if ($result)
				{
					$data = array();
					while ($r = $this->db->getRow($result))
					{
						$data[$r[$value['fk']]] = $r['title'];
					}

					$this->config->RENDER[] = array($value['pk'], "select", $data, $value['default']);
				}
			}
		}
	}

	public function delete()
	{
		if ($this->item['_state'] <= 1 )
		{
			$this->updateData(array('_state' => 2));
			return 1;
		}
		// удаляем насовсем
		else
		{
			$this->model->delete('{'.$this->idField.'}='.$this->id);
			RequestInfo::free($this->idGetVar);
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

					if ($this->ajax_update)
					    $this->postData[$fieldName] = iconv('UTF-8', 'CP1251', $this->postData[$fieldName]);
					RequestInfo::free($this->prefix.$fieldName);
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
			RequestInfo::set($this->idGetVar, $this->id);
		}

		return true;
	}

	protected function updateData($data)
	{
		$this->model->update( $data, $this->model->quoteFieldShort($this->idField).'='.DBModel::quote($this->id) );
	}

	protected function needAjaxUpdate()
	{
		return $_POST["ajax_update"] ? true : false;
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
		$tpl =& $this->tpl;

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
					if ( isset($this->postData[ $field ]) )
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
		}
		if ( $this->ajax_update )
		    return;

		//supertag
		if ( $this->config->supertag)
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

			if ($_POST[$this->prefix . '_supertag'] == '')
			{
				Finder::useClass('Translit');
				$translit =& new Translit();
				$this->postData['_supertag'] = $translit->supertag( $this->postData[$field], TR_NO_SLASHES, $limit );
			}

			if ($this->config->supertag_check || $this->config->supertag_path_check)
			{
				$sql = "SELECT id, _supertag
				        FROM ??".$this->config->table_name."
						WHERE  _supertag=".$this->db->quote($this->postData['_supertag'])." AND id <> ".intval($this->id);

				if ($this->config->supertag_path_check)
				{
					$item = $this->db->queryOne("SELECT _parent FROM ??".$this->config->table_name." WHERE id = ".intval($this->id));
					$sql .= ' AND _parent = '.intval($item['_parent']);
				}

				$rs = $this->db->queryOne($sql);
				if ($rs['id'])
				{
					if (!$this->id)
					{
						$this->updateSupertagAfterInsert = true;
					}
					else
					{
						$this->postData['_supertag'] .= '_'.$this->id;
					}
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
		$data = array('_order' => $this->id);

		if ($this->updateSupertagAfterInsert)
		{
			$data['_supertag'] = $this->postData['_supertag'].'_'.$this->id;
		}
		$this->updateData($data);
	}

	public function setId($id)
	{
     	$this->id = $id;
	}
}

?>
