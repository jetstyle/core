<?php
/**
 *  Страница для отображения хендлера
 */

$this->UseClass("controllers/BasicPage");
class HandlerPage extends BasicPage
{

	function handle()
	{
		$handler = $this->config['handler'];
		$status = $this->rh->executeHandler($handler);
		// lucky@npj: х.з. какой статус у этих хендлеров, так что..
		return True;
	}

}	


?>
