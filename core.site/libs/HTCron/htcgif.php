<?php

// Call htCron from other site or non-PHP pages

header("Content-Type: image/gif");
header("Content-Disposition: inline;filename=z.gif");
echo base64_decode("R0lGODlhAQABAID/AMDAwAAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOx"); 
flush();
include("htcron.php");
$db = array(
 "host"     => "localhost",
 "user"     => "user",
 "database" => "base",
 "password" => "***",
);
htcCycle($db, "htcron"); //htcron is table name

?>