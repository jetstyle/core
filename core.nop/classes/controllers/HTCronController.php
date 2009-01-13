<?php
/**
 *  Cron
 *
 */
Finder::useClass("controllers/Controller");

class HTCronController extends Controller
{
	function handle()
	{
		ob_end_clean();
		header("Content-Type: image/gif");
		header("Content-Disposition: inline;filename=z.gif");
		echo base64_decode("R0lGODlhAQABAID/AMDAwAAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOx"); 
		flush();
		
		Finder::useLib('HTCron');
		htcCycle( Locator::get('db'), "??htcron" );
		die();
	}
}	
?>