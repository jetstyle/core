<?php
Finder::useClass('blocks/Block');
class ModuleHelpBlock extends Block
{	
	protected function constructData()
	{
		$data = '';
		if (Config::get('db_disable'))
		{
			$this->setData($data);
			return;
		}
		
		$tag = $this->getTplParam('tag');
		if ($tag)
		{
			$data = Locator::get('db')->queryOne("SELECT id, text_pre AS text FROM ??help_texts WHERE _supertag = ".Locator::get('db')->quote($tag)." AND _state = 0");
		}
		
		$this->setData($data);
	}
	
}
?>