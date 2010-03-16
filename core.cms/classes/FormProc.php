<?php

class FormProc extends FormSimple
{


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

                Finder::useClass("forms/EasyForm");
		//$config['success_url'] = RequestInfo::$baseUrl.$this->url_to('thank');
                //$config['on_after_event'] = array(array(&$this, 'OnAfterEventForm'));
		$form = new EasyForm('cms-form', $config);
                
                $this->getFieldsHtml();

		Locator::get('tpl')->set('Form', $form->handle());

//		$tpl->set('___form', );
//		$this->renderButtons();
		$result = $tpl->parse($this->template);

		$tpl->popContext();
		Finder::popContext();

		return $result;
	}
        
        public function getFieldsHtml()
	{
                $item = &$this->getItem();
                $fields_config = array();
                foreach ($item as $name => $it)
                {
                    $fields_config[ $name ] = $this->createField($name, $it);
                }
                
                return $form_config;
        }
        
        function createField($name, $row)
        {
            
            $ret = array("extends_from"=> "string");
            $ret = array_merge($ret, $this->config["form"]["fields"][$name]);
            return $ret;
        }

	public function __getFieldsHtml()
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

	public function &getItem()
	{
		$this->load();
		return $this->item;
	}


	protected function renderButtons()
	{
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

}
?>