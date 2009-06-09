<?php
Finder::useClass('blocks/Block');
class GoogleAnalyticsBlock extends Block
{
	protected function constructData()
	{
		$data = '';
		
		if (Config::exists('google_api_code'))
		{
			$data = Config::get('google_api_code');
		}

		$this->setData( $data );
	}
}
?>