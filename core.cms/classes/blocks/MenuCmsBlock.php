<?php
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
		$data = DBModel::factory($this->config['model'])->load();
		foreach ($data as $item)
		{
			if ($item['href'] == implode ("/", $params)) return $item;
		}
		return false;
	}	
        
        public function markItem(&$model, &$row)
	{
                parent::markItem($model, $row);
        }
}
?>