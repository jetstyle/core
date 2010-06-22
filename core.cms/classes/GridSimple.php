<?php

class GridSimple extends ListSimple implements ModuleInterface
{
    protected $template = "grid_simple.html";
    protected $template_list = "grid_simple.html:list";
        
    protected $columns = array("picture_small"=>"", "title"=>array("title"=>"Название"), "price"=>array("title"=>"Цена", "editable"=>1), /*"items_count"=>array("title"=>"В наличии", "editable"=>1)*/);

    public function handle()
	{

		if ($this->needDelete())
		{
			$redirect = $this->delete();
		}
		elseif ($this->needUpdate() || $this->needAjaxUpdate())
		{
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

        Locator::get("tpl")->set("hide_order", 1);
        
        Locator::get('tpl')->set('group_operations', $this->config['group_operations']);
        Locator::get('tpl')->set('group_delete_url', RequestInfo::hrefChange('',array('delete_list'=>'1')));
        Locator::get('tpl')->set('group_restore_url', RequestInfo::hrefChange('',array('restore_list'=>'1')));
        Locator::get('tpl')->set( '_add_new_title', $this->config['add_new_title'] ? $this->config['add_new_title'] : "Добавить" );
                
        Locator::get('tpl')->set( '_add_new_href', RequestInfo::hrefChange( $this->config['add_new_href']  ? RequestInfo::$baseUrl."do/".$this->config['add_new_href'] : "", array($this->idGetVar => '', '_new' => 1, 'rubric_id'=>$this->id)) );
                
        Locator::get('tpl')->set( '_delete_title', $this->config['delete_title'] ? $this->config['delete_title'] : "Удалить" );
        
        $this->load();
        
        $data = array();
            
        $data[] = array("cols"=>$this->columns);

        if ($this->items)
        {
            foreach ($this->items as $k => $r)
            {
                //echo($r);die();
                $row  = array();
                $cols = array();
                $href = Router::linkTo( "Do" )."/".$this->config["link_to"]."?id=".$r["id"];
                foreach ( $this->columns as $col=>$col_title )
                {
                
                    $cols[$col] = array("title"=>$r[$col]);
                
                    if ($col == "title" || count($cols)==1 )
                        $cols[$col]["href"] = $href;
             
                    if ( $data[0]['cols'][$col]["editable"] )
                    {
                        $cols[$col]["editable"] = 1;
                    }
                    //ссылки сортировки
                    $order = $this->getOrderValueFor($col);
                    $data[0]['cols'][$col]["href"] = RequestInfo::hrefChange('',array('order'=>$order  )) ;
                
                    //стрелочки сортировки
                    if ( $this->current_order == $col || ( $this->columns[$col]["order"] && $this->current_order == $this->columns[$col]["order"]) )
                        $data[0]['cols'][$col]["dir"] = $order[0] == "_" ? "up" : "down";
                }
                if ( $r["_state"]!=0 )
                    $row["class"] = "deleted";
                $row["id"] = $r["id"];
                $row["cols"] = $cols;
                $row["href"] = $href;
                //var_dump( $row );
                //echo '<br>';
                $data[] = $row;
            }
        
       	    if ( $this->config["hide_order"] ){
       	         Locator::get('tpl')->set('hide_order', 1);
       	    }
        
            //стрелочки
            if ($this->current_order=="_order")
            {
                 Locator::get('tpl')->set('order_dir', ( $this->order_dir=="DESC" ? "down" : "up") );
                 Locator::get('tpl')->set('order_href', RequestInfo::hrefChange('',array('order'=>( $this->order_dir=="DESC" ? "_order" : "__order")  )) );
            }
            Locator::get('tpl')->set('order_href_default', RequestInfo::hrefChange('',array('order'=>"_order")  )) ;

            Locator::get('tpl')->set('Items', $data);
        }
        $this->renderFilters();
        $this->renderPager();
        Locator::get('tpl')->set( 'prefix', $this->prefix );
    }

    private function getOrderValueFor($col)
    {
        $order = RequestInfo::get("order");
        if ($order==$col)
        {
            $this->dir = "DESC";
            return "_".$col;
        }
        else{
            $this->dir = "ASC";
            return $col;
        }
    }

	public function load( $where = '' )
	{
		if( !$this->loaded )
		{
			$total = $this->getTotal($where);

			if ($total > 0)
			{
				$this->pager($total);

				$model = &$this->getModel();
                
                $this->prepareOrder();

                $model->setOrder($this->current_order." ".$this->order_dir);

                $this->setWhere($model);

                //for complex
                //$model->addField('>items2rubrics', array("model"=>"CatalogueComplexItems2Rubrics", "pk"=>"id", "fk"=>"item_id", "where"=>"{rubric_id}=".$this->id ));
                                
				$model->load( $where, $this->pager->getLimit(), $this->pager->getOffset());
				$this->items = &$model->getData();
			}

			$this->loaded = true;
		}
	}
	
	public function prepareOrder()
	{
        $order = RequestInfo::get("order");
        switch( $order ){
            case "price":
                $this->current_order = "price";
                $this->order_dir = "ASC"; break;
            case "_price":
                $this->current_order = "price";
                $this->order_dir = "DESC"; break;
            case "title":
                $this->current_order = "title";
                $this->order_dir = "ASC"; break;
            case "_title":
                $this->current_order = "title";
                $this->order_dir = "DESC"; break;
            case "items_count":
                $this->current_order = "items_count";
                $this->order_dir = "ASC"; break;
            case "_items_count":
                $this->current_order = "items_count";
                $this->order_dir = "DESC"; break;
            case "__order":
                $this->current_order = "_order";
                $this->order_dir = "DESC"; break;
            case "_order":
            default:
                $this->current_order = "_order";
                $this->order_dir = "ASC";
        }

	}
        
    //every FormClass should realise it
    public function setWhere(&$model){
        return;
    }
        
    public function update($updateData=null)
    {
                //ajax update
		if ($this->needAjaxUpdate())
		{
    			
    			header('Content-Type: text/html; charset=windows-1251');
                        if ($_POST["action"]=="reorder")
                        {
                            $orders = RequestInfo::get("orders");
                            foreach ($orders as $order=>$id)
                            {
        //                        var_dump($order, $id);
                                if (is_numeric($id)){
                                    $data = array("_order"=>$order);
                                    $this->getModel()->loadOne("{id}=".$id)->update($data, "id=".$id);
                                }
                            }
                            die('200 ok');
                        }
                        
                        die('500 unkown action');
    			//die($postData[ $_POST['ajax_update'] ]);
		}
                
                //common update
            $updateFields = $this->getUpdateFields();
            
            if ($updateFields){
                foreach ( $updateFields as $field )
                {
                    foreach ($_POST[$field] as $f=>$v)
                    {
                        //echo '<br>update SET '.$field."=".$v." WHERE id=".$f;  
                        $data = array( $field => $v );
                        $this->getModel()->update( $data, "{id}=".$f);
                    }
                }
            
            }
            return true;
    }
        
    public function delete(){
        if (!empty($_POST["selected_items"]))
        {
            foreach ($_POST["selected_items"] as $id)
	{
                //var_dump($id);
                $this->getModel()->deleteToTrash(intval($id));
	}
            //$where = "{id} IN ".DBModel::quote($_POST["selected_items"]);
            //die($where);
            //$this->getModel()->delete($where);
        }
        return true;
    }

    protected function getUpdateFields(){
            foreach($this->columns as $field=>$col)
            {
                if ($col["editable"])
                    $updateFields[] = $field;
            }
            return $updateFields;
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
}
?>
