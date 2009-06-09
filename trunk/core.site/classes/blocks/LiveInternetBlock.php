<?php
Finder::useClass('blocks/Block');
class LiveInternetBlock extends Block
{

	protected function constructData()
	{
		$data = '';
		
		if (Config::exists('counter_li_ru'))
		{
			$data = Config::get('counter_li_ru');
		}

		$this->setData( $data );
	}

}
?>