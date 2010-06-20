<?php

class PopupObjects
{
	protected $db = null;
	protected $upload = null;
	protected $rubricId = 0;			// текущая рубрика
	protected $rubrics = array();		// массив со всеми рубриками array(0 => array('id' => 0, 'title' => 'hello'), ....)
	protected $rubricsLoaded = false;
	protected $rubricsTable = "files_rubrics";
	protected $rubricsTypeId = 0;

	protected $items = array();
	
	protected $model = null;

	protected $configKey = '';
        
        protected $perPage = 10;

	public function __construct()
	{		
		$this->db = &Locator::get('db');
	}

	public function setRubric($value)
	{
                //if (isset($value))
                    $this->rubricId = intval($value);
              //  else
               //     $this->rubricId = -1;
	}
        
        public function setAlbum($value)
	{
		$this->albumId = $value;
	}
        
        public function setExpand($expand_config){
                $this->expand_config = $expand_config;
        }
	
	public function setRubricsTypeId($value)
	{
		$this->rubricsTypeId = $value;
	}
	
	public function setConfigKey($value)
	{
		$this->configKey = $value;
	}

	public function getRubrics()
	{
		if (!$this->rubricsLoaded)
		{
			$this->rubricsLoaded = true;
			$this->loadRubrics();
		}
		return $this->rubrics;
	}

	public function getItems()
	{
		$model = &$this->getModel();
		
		$total = $model->getCount();		
		if (0 == $total)
		{
			return $this->items;
		}

		$model->load();
		
		return $model->getArray();
	}
        
        public function setPerPage($perPage)
        {
            if ( $perPage > 0 )
                $this->perPage = $perPage;
        }

	public function getPages()
	{
		return $this->getModel()->getPages();
	}
	
	protected function &getModel()
	{
		if (!$this->model)
		{
			Finder::useModel('DBModel');
			$this->model = DBModel::factory('FilesModel/cms_list');
			
			if ( $this->albumId )
			{
			    $ids = DBModel::factory( $this->expand_config["model_items"] )->load("rubric_id=".DBModel::quote($this->albumId))->getArrayAssoc("id");
                            $this->model->addField(">files2obj", array("model"=>"Files2RubricsModel/files2objects","fk"=>"file_id", "pk"=>"id", "where"=>" {obj_id} IN (".DBModel::quote( array_keys($ids) ).")"));
                            /*
                            $this->model->getForeignModel('files2obj.object')->setModel();
                            
                            $sql = "SELECT id FROM ??gallery_items WHERE rubric_id = ".DBModel::quote($this->albumId);
			    $gallery_ids = $this->db->query($sql);
                            */
			 //echo   $this->where = ;
			}	
                        
			$this->model->where = $this->model->where . ($this->where ? ($this->model->where ? ' AND ' : '') . $this->where : '') ;
			
			$this->model->enablePager();
			$this->model->setPagerPerPage($this->perPage);
			
			$this->model->addFilesConfig(array('config' => $this->configKey, 'lazy_load' => false));
			
			$files2rubricsModel = &$this->model->getForeignModel('rubric');
			$files2rubricsModel->where = $files2rubricsModel->where.( $files2rubricsModel->where ? " AND " : "" ). "{rubric.type_id} = ".DBModel::quote($this->rubricsTypeId);
			
			$this->model->setOrder('{_created} DESC'); // {rubric._order} ASC 

			if ($this->rubricId>0)
			{
				// condition on rubric
				$files2rubricsModel->where = $files2rubricsModel->where.( $files2rubricsModel->where ? " AND " : "" ). "{rubric_id} = ".DBModel::quote($this->rubricId);
			}
                        /*else if ($this->rubricId==0)
                        {
                                $files2rubricsModel->where = "";
                        }*/
                        //var_dump($files2rubricsModel->where);
		}

		return $this->model;
	}

	public function handlePost()
	{										                                

            if ( $_POST['rubric_id']=="create" &&  $_POST['new_rubric'] )
            {
                    $o = $this->getModel()->getForeignModel('rubric')->getForeignModel('rubric')->setOrder("_order desc")->loadOne("_state=0");

                    $data = array("title"=>iconv("utf-8", "cp1251", $_POST['new_rubric']), "type_id"=>1, "module"=>"Files", "_order"=>($o["_order"]+1) );

                    $rubricId = $this->getModel()->getForeignModel('rubric')->getForeignModel('rubric')->insert($data);
                    echo $rubricId;
                    die();
            }
	    elseif (is_uploaded_file($_FILES['file']['tmp_name']))
	    {			
		    $rubricId = intval($_POST['rubric_id']);

                    if ($rubricId==-1)
                    {
                            $r = $this->getModel()->getForeignModel('rubric')->getForeignModel('rubric')->loadOne("is_default=1")->getArray();
                            $rubricId = $r["id"];
                    }               
                    
                    if (!$rubricId)
		    {
			    if ($_POST["ajax"])
                            {
				$ret = array("error"=>"Не указана рубрика! \r\nPOST: ".var_export($_POST, true));
				echo Json::encode($ret);
				die();
			    }
		            else
				Locator::get('tpl')->set('file_err', 'Поле "Рубрика" обязательно для заполнения');
			    return;
		    }
                    
		
		    $file = FileManager::getFile($this->configKey.':picture');

		    try
		    {						
			    $file->upload($_FILES['file']);
		    }
		    catch(UploadException $e)
		    {
			    Locator::get('tpl')->set('file_err', $e->getMessage());
		    }

		    if ($file['id'])
		    {
			    $file->addToRubric($rubricId);

			    $data = array(
				    'title' => $_POST['title'],
				    'title_pre' => Locator::get('tpl')->action('typografica', $_POST['title'])
			    );
			
			    $file->updateData($data);
                            
                            if ($_POST["ajax"])
                            {
                                //echo $file["id"]."|".$file["link"];
                                $ret = $this->getModel()->loadOne("{id}=". $file["id"] )->getArray();
                                //$ret = $file->getArray();
                                echo Json::encode($ret);
                                die();
                            }
		    }
	    }

	}

	protected function loadRubrics()
	{
		$rubrics = $this->db->query("
			SELECT id, title, module
			FROM ??".$this->rubricsTable."
			WHERE type_id = ".$this->db->quote($this->rubricsTypeId)." AND _state = 0 ORDER by _order
		", "id");

        //$albums = $this->db->query("select id,title from ??gallery_rubrics WHERE _state=0", "id");

        if ($this->expand_config["model_rubrics"])
                $albums = DBModel::factory( $this->expand_config["model_rubrics"] )->load("_state=0")->getArrayAssoc("id");

		//var_dump($albums);die();
		if (isset($albums[$this->albumId]))
		{
			$albums[$this->albumId]['selected'] = true;
		}
		
		foreach ($rubrics as $i=>$rubric)
		{
		    if ( $rubric["module"]=="Gallery" ){
			$rubric["children"] = $albums;
		    }
		    $this->rubrics[ $rubric["id"] ] = $rubric;
		}


		// mark current rubric
		if (isset($this->rubrics[$this->rubricId]))
		{
			$this->rubrics[$this->rubricId]['selected'] = true;
		}
	}
}

?>
