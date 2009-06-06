<?php

Finder::useClass("controllers/Controller");
class MaintenanceController extends Controller
{
	function handle()
	{
		$exceptionsDir = Config::get('cache_dir').'exceptions/';
        $logFile = $exceptionsDir.'log';

        if (file_exists($logFile) && filesize($logFile) > 1024*1)
        {
            for ($i = 5; $i >= 1; $i--)
            {
                $_logFile = $exceptionsDir.'log-'.$i;
                if (file_exists($_logFile))
                {
                    if ($i == 5)
                    {
                        unlink($_logFile);
                    }
                    else
                    {
                        rename($_logFile, $exceptionsDir.'log-'.$i+1);
                    }
                }
            }

            rename($logFile, $_logFile);
        }
        
		die();
	}
}

?>