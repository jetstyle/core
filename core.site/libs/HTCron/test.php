Test 4 htcron.
<?php
ob_end_flush();
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