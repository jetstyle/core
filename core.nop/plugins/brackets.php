<?php
    /*
    
    */

    $ret = preg_replace('/\[([^\]]+)\]/', "<a href='".$params[0]."'>$1</a>", $params['_']);
    echo $ret;

?>