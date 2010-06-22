<?php 
/**
 * TODO: make it work with *Tree models
 * the task is QUICKSTART-1068
 * nop@jetstyle.ru
 */

Finder::useClass('blocks/MenuBlock');
class MenuCmsBlock extends MenuBlock
{	
	public function getCurrent()
	{
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
			    return $item;
			}
		}

		return false;
	}

    public function markItem(&$model, &$row)
	{
        parent::markItem($model, $row);
    }
}
?>
