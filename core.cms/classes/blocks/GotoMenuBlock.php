<?php
Finder::useClass('blocks/Block');
class GotoMenuBlock extends Block
{	
	protected function constructData()
	{
		$data = Locator::get('db')->query("
 			SELECT IF(LENGTH(title_short) > 0, title_short, title_pre) AS title, _path AS path
 			FROM ??content
 			WHERE controller NOT IN ('', 'content', 'link') AND _state = 0
 			ORDER BY _level,_order
 		");
		$this->setData($data);
	}	
}
?>
