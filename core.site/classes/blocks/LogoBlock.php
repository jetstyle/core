<?php
Finder::useClass('blocks/Block');

class LogoBlock extends Block
{
	protected function constructData()
	{
                Finder::useClass('FileManager');

                $file = FileManager::getFile('Config/config:logo/small', 1);

		$this->setData($file);
	}
}
?>