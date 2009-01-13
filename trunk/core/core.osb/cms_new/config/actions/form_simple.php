<?
/*
	Корректирует шаблоны формы - развешивает классы на таблицу.
	Эвристически.
*/
function action_form_simple( &$rh, &$PARAMS ){

	return preg_replace(
		"/<table\s*border\='0'\s*>/i",
		"<table cellspacing=\"0\" cellpadding=\"0\" class=\"cms-simple-form w100\">",
		$PARAMS['__string']
	);
}

?>