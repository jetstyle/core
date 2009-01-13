<?php
if (isset($params['_']))
{
	echo $params['_'];
}
elseif (isset($params[0]))
{
	echo $params[0];	
}
else
{
	echo $params['name'];
}

?>