<?php

/*		{{!!repeat 10}}
         item <br />
      {{/!!}}
*/

$count = $params[0]; 

for ( $i =1; $i<=$count; $i++ ){
  echo $params['_'];
}

?>