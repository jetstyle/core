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
		    //var_dump($redirect);die();
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
	
	//вызывать ли $this->update() 
	//для этого у неас есть onFormAfterEvent
	public function needUpdate()
	{
		return false;
	}

	public function getHtml()
	{
        Finder::pushContext();
        Finder::prependDir(Config::get('cms_dir').'modules/'.$this->config['module_name'].'/');

        $tpl = &Locator::get('tpl');
        $tpl->pushContext();
        /*
        $tpl->set( 'prefix', $this->prefix );
        $tpl->set( '__form_name', $this->prefix.'_simple_form' );
        */
        /*
        * TODO:
        if ($this->insert_fields)
        {

            $tpl->set('hidden_fields', $this->insert_fields);
        }*/

        $item = &$this->getItem();

        /**
         * TODO: ajax
        if ( $item['id']>0 || RequestInfo::get('_new') )
        {
	            $tpl->set( 'ajax_url', RequestInfo::href() );
        }
        */

        Finder::useClass("forms/EasyForm");
        
        Locator::get("msg")->load("cms");
        $config = array();
        $config["fields"]   = $this->getFieldsConfig();
        $config["fields"]   += $this->getFilesConfig();
        $config["db_model"] = $this->config["model"];
        $config["on_before_event"] = $this;
        $config["on_after_event"] = $this;
        $config['success_url'] = RequestInfo::href();

        //if in edit mode: change button and load model item
        if ($this->id){
            $config["id"] = $this->id;
            $config["buttons"] = array("save", "delete");
        }

        //default form is in core/core.cms/classes/forms/cms-form.yml
        $form = new EasyForm('cms-form', $config);

		Locator::get('tpl')->set('Form', $form->handle());

//		$tpl->set('___form', );
//		$this->renderButtons();

		$result = $tpl->parse($this->template_item);

		$tpl->popContext();
		Finder::popContext();

		return $result;
	}


    private function getGroup($group)
    {
        $fields_config = array();  
        $model_fields = $this->getModel()->getForeignFields();
        $model = $this->getModel();
        //var_dump($model_fields);
        foreach ($group as $name=>$group_field)
        {
            //у поля есть дочерние поля (группа)
            if ( $group_field["fields"] ){
               //var_dump($name, $group_field["fields"]);

                $fields_config[ $name ] = $group_field;
                $fields_config[ $name ]["fields"] = array_merge($fields_config[ $name ]["fields"], $this->getGroup($group_field["fields"]));
            }
            //поле - это поле
            else
            {
/*              FIXME: clean after fk_select

                if ($group_field["extends_from"]=="fk_select" && $model[$name])
                {
                    $group_field["fk_model"] = $model[$name];
                    //var_dump($group_field["fk_model"]);
                }
*/

                $fields_config[ $name ] = array_merge($group_field, $this->createField($name, $group_field));
                

                /*
                if ($fields_config[ $name ]["extends_from"]=="fk_select" && $model_fields[$name])
                {
                    $model = $this->getModel();
                    echo $opts = DBModel::factory($model[$name])->load(); //$model_fields[$name]['className']
                    var_dumP($opts);
                    $fields_config[ $name ]["options"] = $opts;
                }
                */
               // echo '<hr>';
               
            }
        }
        
        return $fields_config;
    }

    /**
     * creates fields config for EasyForm
     */
    public function getFieldsConfig()
	{
        $fields_config = array();

        //model table fields
        $item = $this->getModel()->getTableFields();

        if ($this->config["fields"])
        {
        
            $fields_config = $this->getGroup( $this->config["fields"] );

        }
        else
        {
            foreach ($item as $name => $row)
            {
                $fields_config[ $name ] = $this->createField($name, $row);
            }
        }
        //var_dumP($fields_config["controller"]);
        //echo '<hr><hr><hr>';
       //die();

        return $fields_config;
    }

    /**
     * extend model fields with form->config[default_packages] (core/core.cms/classes/forms/cms-form.yml)
     */
    function createField($name, $field_cfg)
    {
        $title = Locator::get("msg")->get( "forms.".$name );
        $ret = array( "wrapper_title"=> $field_cfg["wrapper_title"] ? $field_cfg["wrapper_title"] : $title ); 

        if ( is_array($this->config["fields"][$name] ) ){
            $ret = array_merge($this->config["fields"][$name], $ret);
          
        }

        return $ret;
    }

    /**
     * creates files config from module/config.yml to EasyForm
     */
    public function getFilesConfig()
	{
        $fields_config = array();
        if ( $this->config["files"] )
        {
            $model  = $this->getModel();

            foreach ($this->config["files"] as $name => $conf)
            {
                $fields_config[ $name ] = $this->createField($name, $row);
                $fields_config[ $name ]["extends_from"] = "file_cms";
                $fields_config[ $name ]["file_ext"] = explode(",",Config::get("upload_ext"));
                $fields_config[ $name ]["file_size"] = 55242880;
                
                //TODO: just assign file field from model
                $fields_config[ $name ]["variants"] = $conf["variants"];

                $fields_config[ $name ]["config_key"] = $model->getFilesConfigKey().':'.$name;
            }
        }
        return $fields_config;
    }

	protected function renderButtons()
	{
	}


	protected function renderFields()
	{
	die();
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
die();
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

    function OnBeforeEventForm($event, $form)
    {
        if ( FORM_EVENT_DELETE==$event["event"] )
        {
            $this->delete();
            $form->success = true;
            $form->processed = true;
            $form->deleted = true;
        }
    }
}
?>

