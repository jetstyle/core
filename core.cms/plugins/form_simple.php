<?php
	return preg_replace(
		"/<table\s*border\='0'\s*>/i",
		"<table cellspacing=\"0\" cellpadding=\"0\" class=\"cms-simple-form w100\">",
		$params['_']
	);
?>