<?php
Finder::useClass('blocks/Block');
class MenuBlock extends Block
{	
	protected function constructData()
	{
		if (Config::get('db_disable') || RequestInfo::get('hide_toolbar') || !Locator::get('principal')->security('noguests'))
		{
			$this->setData(array());
			return;
		}
		
 		Finder::useClass('Toolbar');
		$toolbar = new Toolbar();
		
		$data = $toolbar->getData();
		$data['goto'] = $toolbar->getGoToList();

		$this->setData($data);
	}	
}
?>