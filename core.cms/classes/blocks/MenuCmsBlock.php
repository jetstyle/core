<?php 
/**
 * TODO: make it work with *Tree models
 * the task is QUICKSTART-1068
 * nop@jetstyle.ru
 */

Finder::useClass('blocks/MenuBlock');
class MenuCmsBlock extends MenuBlock
{	

    private $defaultMenuItem;
	public function getCurrent()
	{
		if ($this->current)
		{
			return $this->current;
		}
		
        $params = array();
 		if (Locator::exists('controller'))
		{
 			$params = Locator::get('controller')->getParams();
		}

        $model = DBModel::factory($this->config['model']);

        //we need this to show siblings of hidden current element
        if ($this->config["mode"]=="submenu"){
            $model->setWhere( "({_state}=0 OR {_state}=1)" );
        }

		$data = $model->load();
               
		foreach ($data as $item)
		{
			if ($item['href'] == implode ("/", $params))
			{
                // echo($item);
				$this->current = $item;
			    return $this->current;
			}
		}

		return false;
	}

	protected function constructData()
	{
	    parent::constructData();
	    $data = $this->data;
	    foreach ($data as $i=>$r)
	    {
	        if ( $r["is_granted"] ){
	            if ( ! $this->defaultMenuItem )
	                $this->defaultMenuItem = $r;
	            $out[] = $r;
	        }
	    }
	    
	    $this->setData($out);
	}

    public function getDefaultMenuItem()
    {
        if (!$this->defaultMenuItem )
            $this->constructData();
        return $this->defaultMenuItem;
    }

    public function markItem(&$model, &$row)
	{
        parent::markItem($model, $row);
        $modulePath = $row["href"];
        if ( Locator::get('principal')->security('cmsModules', $modulePath) )
        {
            $row["is_granted"] = true;
        }
		
		if ($this->config["mode"] == "submenu") {
			$item = $this->getCurrent();
			$module = ModuleConstructor::factory($row['href']);
			$moduleConfig = $module->getConfig();
			$moduleForm = $module->getForm();
			$moduleFormConfig = $moduleForm->getConfig();
			if ($moduleConfig['link_to'] == $item['href'] || $moduleFormConfig['link_to'] == $item['href'])
			{
				$row['selected'] = true;
			}
		}
    }
}
?>
