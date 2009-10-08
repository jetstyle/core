<?php
if (!Locator::exists('controller')) return;

$controller = Locator::get('controller');
if($controller['meta_title'])
{
	echo html_entity_decode(strip_tags($controller['meta_title']));
}
else
{
	echo html_entity_decode(strip_tags($controller['title']));
	//echo Config::get('project_title');
}
?>